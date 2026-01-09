<?php

namespace Database\Factories;

use App\Enums\StoreLevel;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => fake()->company(),
            'level' => StoreLevel::RETAIL,
            'address' => fake()->address(),
            'phone' => fake()->e164PhoneNumber(),
        ];
    }

    public function center(): static
    {
        return $this->state(fn () => [
            'level' => StoreLevel::CENTER,
            'parent_id' => null,
        ]);
    }

    public function branch(Store $parent): static
    {
        return $this->state(fn () => [
            'level' => StoreLevel::BRANCH,
            'parent_id' => $parent->id,
        ]);
    }

    public function retail(Store $parent): static
    {
        return $this->state(fn () => [
            'level' => StoreLevel::RETAIL,
            'parent_id' => $parent->id,
        ]);
    }
}
