<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'method' => 'cash',
            'amount_tendered' => 50000,
            'amount_paid' => 50000,
            'change_amount' => 0,
            'status' => 'paid',
            'paid_at' => now(),
        ];
    }
}
