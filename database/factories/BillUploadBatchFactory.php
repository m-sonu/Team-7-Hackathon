<?php

namespace Database\Factories;

use App\Models\BillUploadBatch;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillUploadBatch>
 */
class BillUploadBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'currency' => 'NRP',
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'category_monthly_pivot_id' => null,
        ];
    }
}
