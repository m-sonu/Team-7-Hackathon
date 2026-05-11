<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\BillStatus;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    /**
     * Define the model's default state.aaaa
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::all()->random()->id,
            'bill_no' => fake()->bothify('BILL-####'),
            'vat_no' => fake()->bothify('VAT-####'),
            'amount' => fake()->randomFloat(2, 50, 500),
            'approve_amount' => function (array $attributes) {
                return $attributes['amount'];
            },
            'status' => $this->faker->randomElement([BillStatus::VERIFIED, BillStatus::INVALID, BillStatus::REJECTED, BillStatus::REIMBURSED]),
            'raw_text' => fake()->paragraph(),
            'category_monthly_pivot_id' => null, // Will be set in seeder
        ];
    }
}
