<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(Str::random(8)),
            'price' => fake()->randomFloat(2, 1, 1000),
            'description' => fake()->sentence(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
