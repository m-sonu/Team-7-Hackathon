<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\BillItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillItem>
 */
class BillItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bill_id' => Bill::factory(),
            'name' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 10, 200),
            'is_claimable' => true,
            'rejection_reason' => null,
        ];
    }
}
