<?php

namespace Database\Factories;

use App\Models\CryptoAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source_crypto_account_id' => CryptoAccount::factory(),
            'destination_crypto_account_id' => CryptoAccount::factory(),
            'coin_type' => 'bitcoin',
            'amount' => fake()->randomFloat(8, 0.0001, 5),
        ];
    }
}
