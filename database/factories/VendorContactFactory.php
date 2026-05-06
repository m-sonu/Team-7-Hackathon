<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\VendorContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorContact>
 */
class VendorContactFactory extends Factory
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
            'company_name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
        ];
    }
}
