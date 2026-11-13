<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CryptoAccount>
 */
class CryptoAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'coin_type' => fake()->randomElement(['bitcoin', 'ethereum', 'litecoin']),
            'balance' => 0,
        ];
    }
}
