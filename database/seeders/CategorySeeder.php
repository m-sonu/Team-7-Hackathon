<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::query()->insert([
            [
                'name' => 'System Usage Fees',
                'monthly_limit' => 3000,
                'is_active' => true,
            ],
            [
                'name' => 'Entertainment Expenses',
                'monthly_limit' => 3000,
                'is_active' => true,
            ],
            [
                'name' => 'Travel and Transportation Expenses',
                'monthly_limit' => 3000,
                'is_active' => true,
            ],
            [
                'name' => 'Meeting Expenses',
                'monthly_limit' => 3000,
                'is_active' => true,
            ],
            [
                'name' => 'Supplies Expenses',
                'monthly_limit' => 3000,
                'is_active' => true,
            ],
            [
                'name' => 'Employee Welfare Expenses',
                'monthly_limit' => 3000,
                'is_active' => true,
            ],
            [
                'name' => 'Communication Expenses',
                'monthly_limit' => 3000,
                'is_active' => true,
            ],
        ]);

    }
}
