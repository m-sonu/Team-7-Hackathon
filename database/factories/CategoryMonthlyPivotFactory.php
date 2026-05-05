<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryMonthlyPivot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryMonthlyPivot>
 */
class CategoryMonthlyPivotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'month_year' => now()->format('Y-m'),
            'bill_count' => fake()->numberBetween(1, 20),
            'total_spent' => fake()->randomFloat(2, 10, 1000),
            'last_updated_at' => now(),
        ];
    }
}
