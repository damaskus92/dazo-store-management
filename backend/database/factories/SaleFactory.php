<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
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
            'transaction_number' => 'TRX-'.strtoupper(Str::random(8)),
            'store_id' => null,
            'cashier_id' => null,
            'total_amount' => fake()->numberBetween(100, 1000),
            'paid_amount' => 0,
            'change_amount' => 0,
        ];
    }
}
