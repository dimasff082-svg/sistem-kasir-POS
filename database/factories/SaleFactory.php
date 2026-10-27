<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_number' => 'TRX-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->lexify('??????')),
            'user_id' => User::factory(),
            'transaction_date' => now(),
            'subtotal' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'status' => 'completed',
            'notes' => null,
        ];
    }
}
