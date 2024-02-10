<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\CryptoAccount;
use App\Models\Transaction;

class CryptoWalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function testGetUserBalances()
    {
        // Create a user
        $user = User::factory()->create();

        // Create crypto accounts for the user
        CryptoAccount::factory()->create([
            'user_id' => $user->id,
            'coin_type' => 'bitcoin',
            'balance' => 10, // Example balance
        ]);
        CryptoAccount::factory()->create([
            'user_id' => $user->id,
            'coin_type' => 'ethereum',
            'balance' => 20, // Example balance
        ]);

        // Send GET request to /api/crypto-accounts
        $response = $this->actingAs($user)
                         ->get('/api/crypto-accounts');

        // Assert response status code
        $response->assertStatus(200);

        // Assert response JSON structure
        $response->assertJsonStructure([
            '*' => [
                'id',
                'coin_type',
                'balance',
                'created_at',
                'updated_at',
            ]
        ]);
    }

    public function testTransferFunds()
    {
        // Create sender and recipient users
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
    
        // Create crypto account for sender
        $sourceAccount = CryptoAccount::factory()->create([
            'user_id' => $sender->id,
            'coin_type' => 'bitcoin',
            'balance' => 100, // Example balance
        ]);
    
        // Create crypto account for recipient
        $destinationAccount = CryptoAccount::factory()->create([
            'user_id' => $recipient->id,
            'coin_type' => 'bitcoin',
            'balance' => 0, // Initialize recipient's balance
        ]);
    
        // Send POST request to /api/transfer
        $response = $this->actingAs($sender)
                         ->post('/api/transfer', [
                             'recipient_email' => $recipient->email,
                             'source_crypto_account_id' => $sourceAccount->id,
                             'amount' => 50, // Example transfer amount
                         ]);
    
        // Assert response status code
        $response->assertStatus(200);
    
        // Assert that funds were transferred successfully
        $response->assertJson(['message' => 'Funds transferred successfully']);
    
        // Assert sender's balance decreased by the transfer amount
        $this->assertEquals(50, $sender->cryptoAccounts()->where('id', $sourceAccount->id)->first()->balance);
    
        // Assert recipient's balance increased by the transfer amount
        $this->assertEquals(50, $recipient->cryptoAccounts()->where('id', $destinationAccount->id)->first()->balance);
    }
    

    public function testListTransactions()
    {
        // Create a user
        $user = User::factory()->create();

        // Create crypto account for the user
        $cryptoAccount = CryptoAccount::factory()->create(['user_id' => $user->id]);

        // Create transactions for the crypto account
        Transaction::factory()->count(5)->create(['crypto_account_id' => $cryptoAccount->id]);

        // Send GET request to /api/transactions
        $response = $this->actingAs($user)
                         ->get('/api/transactions');

        // Assert response status code
        $response->assertStatus(200);

    }

    // Add tests for conversion endpoint
    public function testConvertBalance()
    {
        // Create a user
        $user = User::factory()->create();

        // Create crypto account for the user
        $cryptoAccount = CryptoAccount::factory()->create([
            'user_id' => $user->id,
            'coin_type' => 'bitcoin',
            'balance' => 100, // Example balance
        ]);

        // Send POST request to /api/convert
        $response = $this->actingAs($user)
                         ->post('/api/convert', [
                             'source_coin' => 'bitcoin',
                             'target_coin' => 'ethereum',
                             'amount' => 50, // Example amount
                         ]);

        // Assert response status code
        $response->assertStatus(200);

        // Assert response JSON structure or message
        // Add more assertions as needed
    }
}
