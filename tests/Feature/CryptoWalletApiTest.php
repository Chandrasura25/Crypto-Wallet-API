<?php

namespace Tests\Feature;

use App\Models\CryptoAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CryptoWalletApiTest extends TestCase
{
    use RefreshDatabase;

    private function account(User $user, string $coin, $balance): CryptoAccount
    {
        return CryptoAccount::factory()->create([
            'user_id' => $user->id,
            'coin_type' => $coin,
            'balance' => $balance,
        ]);
    }

    // ── Auth ─────────────────────────────────────────────

    public function test_register_and_login_return_a_token(): void
    {
        $this->postJson('/api/register', [
            'phone' => '+2348000000000',
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ])->assertCreated()->assertJsonStructure(['message' => ['id', 'email', 'phone'], 'token']);

        $this->postJson('/api/login', ['phone' => '+2348000000000', 'password' => 'secret-password'])
            ->assertOk()->assertJsonStructure(['token']);

        $this->postJson('/api/login', ['phone' => '+2348000000000', 'password' => 'wrong'])
            ->assertUnauthorized();
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/crypto-accounts')->assertUnauthorized();
    }

    // ── Accounts ─────────────────────────────────────────

    public function test_user_sees_only_their_own_balances(): void
    {
        $user = User::factory()->create();
        $this->account($user, 'bitcoin', 10);
        $this->account($user, 'ethereum', 20);
        $this->account(User::factory()->create(), 'bitcoin', 99);

        Sanctum::actingAs($user);

        $this->getJson('/api/crypto-accounts')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure(['*' => ['id', 'coin_type', 'balance']]);
    }

    public function test_cannot_open_two_accounts_for_the_same_coin(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('/api/crypto-accounts', ['coin_type' => 'Bitcoin'])
            ->assertCreated()->assertJsonPath('coin_type', 'bitcoin');
        $this->postJson('/api/crypto-accounts', ['coin_type' => 'bitcoin'])
            ->assertUnprocessable();
    }

    public function test_cannot_read_someone_elses_account(): void
    {
        $other = $this->account(User::factory()->create(), 'bitcoin', 5);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/crypto-accounts/{$other->id}")->assertForbidden();
    }

    // ── Transfers ────────────────────────────────────────

    public function test_transfer_moves_funds_and_records_a_transaction(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $source = $this->account($sender, 'bitcoin', 100);
        $destination = $this->account($recipient, 'bitcoin', 0);

        Sanctum::actingAs($sender);

        $this->postJson('/api/transfer', [
            'recipient_email' => $recipient->email,
            'source_crypto_account_id' => $source->id,
            'amount' => 50,
        ])->assertOk()->assertJson(['message' => 'Funds transferred successfully']);

        $this->assertEquals(50, $source->fresh()->balance);
        $this->assertEquals(50, $destination->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'source_crypto_account_id' => $source->id,
            'destination_crypto_account_id' => $destination->id,
            'coin_type' => 'bitcoin',
        ]);
    }

    public function test_transfer_rejects_overspending(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $source = $this->account($sender, 'bitcoin', 10);
        $this->account($recipient, 'bitcoin', 0);

        Sanctum::actingAs($sender);

        $this->postJson('/api/transfer', [
            'recipient_email' => $recipient->email,
            'source_crypto_account_id' => $source->id,
            'amount' => 10.5,
        ])->assertUnprocessable();

        $this->assertEquals(10, $source->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_rejects_spending_from_someone_elses_account(): void
    {
        $victim = User::factory()->create();
        $thief = User::factory()->create();
        $accomplice = User::factory()->create();
        $victimAccount = $this->account($victim, 'bitcoin', 100);
        $accompliceAccount = $this->account($accomplice, 'bitcoin', 0);

        Sanctum::actingAs($thief);

        $this->postJson('/api/transfer', [
            'recipient_email' => $accomplice->email,
            'source_crypto_account_id' => $victimAccount->id,
            'amount' => 100,
        ])->assertForbidden();

        $this->assertEquals(100, $victimAccount->fresh()->balance);
        $this->assertEquals(0, $accompliceAccount->fresh()->balance);
    }

    public function test_transfer_requires_recipient_to_hold_the_same_coin(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $source = $this->account($sender, 'bitcoin', 10);
        $this->account($recipient, 'ethereum', 0);

        Sanctum::actingAs($sender);

        $this->postJson('/api/transfer', [
            'recipient_email' => $recipient->email,
            'source_crypto_account_id' => $source->id,
            'amount' => 1,
        ])->assertNotFound();
    }

    // ── Transactions ─────────────────────────────────────

    public function test_transaction_history_shows_sent_and_received_only(): void
    {
        $user = User::factory()->create();
        $mine = $this->account($user, 'bitcoin', 0);
        $theirs = $this->account(User::factory()->create(), 'bitcoin', 0);

        Transaction::factory()->create(['source_crypto_account_id' => $mine->id, 'destination_crypto_account_id' => $theirs->id]);
        Transaction::factory()->create(['source_crypto_account_id' => $theirs->id, 'destination_crypto_account_id' => $mine->id]);
        Transaction::factory()->create(); // unrelated

        Sanctum::actingAs($user);

        $this->getJson('/api/transactions')->assertOk()->assertJsonCount(2);
    }

    public function test_cannot_view_an_unrelated_transaction(): void
    {
        $tx = Transaction::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/transactions/{$tx->id}")->assertForbidden();
    }

    // ── Conversion ───────────────────────────────────────

    public function test_convert_uses_live_rate_and_adds_to_target_balance(): void
    {
        Http::fake([
            'api.coingecko.com/*' => Http::response(['bitcoin' => ['usd' => 60000], 'ethereum' => ['usd' => 3000]]),
        ]);

        $user = User::factory()->create();
        $btc = $this->account($user, 'bitcoin', 2);
        $eth = $this->account($user, 'ethereum', 1);

        Sanctum::actingAs($user);

        // 0.5 BTC × 60,000 ÷ 3,000 = 10 ETH
        $this->postJson('/api/convert', ['source_coin' => 'bitcoin', 'target_coin' => 'ethereum', 'amount' => 0.5])
            ->assertOk()
            ->assertJsonPath('converted_amount', '10.00000000');

        $this->assertEquals(1.5, $btc->fresh()->balance);
        $this->assertEquals(11, $eth->fresh()->balance); // added to the existing 1 ETH, not overwritten
    }

    public function test_convert_rejects_amount_above_balance(): void
    {
        Http::fake([
            'api.coingecko.com/*' => Http::response(['bitcoin' => ['usd' => 60000], 'ethereum' => ['usd' => 3000]]),
        ]);

        $user = User::factory()->create();
        $btc = $this->account($user, 'bitcoin', 1);
        Sanctum::actingAs($user);

        $this->postJson('/api/convert', ['source_coin' => 'bitcoin', 'target_coin' => 'ethereum', 'amount' => 2])
            ->assertUnprocessable();

        $this->assertEquals(1, $btc->fresh()->balance);
    }

    public function test_convert_fails_cleanly_when_price_feed_is_down(): void
    {
        Http::fake(['api.coingecko.com/*' => Http::response([], 503)]);

        $user = User::factory()->create();
        $this->account($user, 'bitcoin', 1);
        Sanctum::actingAs($user);

        $this->postJson('/api/convert', ['source_coin' => 'bitcoin', 'target_coin' => 'ethereum', 'amount' => 0.5])
            ->assertStatus(502);
    }
}
