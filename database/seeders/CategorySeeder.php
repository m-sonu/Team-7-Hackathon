<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $monthlyLimit = 3000;
        Category::query()->insert([
            [
                'name' => 'System Usage Fees',
                'jp_name' => 'システム利用料',
                'monthly_limit' => $monthlyLimit,
                'is_active' => true,
            ],
            [
                'name' => 'Entertainment Expenses',
                'jp_name' => '交際費',
                'monthly_limit' => $monthlyLimit,
                'is_active' => true,
            ],
            [
                'name' => 'Travel and Transportation Expenses',
                'jp_name' => '交通費・旅費',
                'monthly_limit' => $monthlyLimit,
                'is_active' => true,
            ],
            [
                'name' => 'Meeting Expenses',
                'jp_name' => '会議費',
                'monthly_limit' => $monthlyLimit,
                'is_active' => true,
            ],
            [
                'name' => 'Supplies Expenses',
                'jp_name' => '消耗品費',
                'monthly_limit' => $monthlyLimit,
                'is_active' => true,
            ],
            [
                'name' => 'Employee Welfare Expenses',
                'jp_name' => '福利厚生費',
                'monthly_limit' => $monthlyLimit,
                'is_active' => true,
            ],
            [
                'name' => 'Communication Expenses',
                'jp_name' => '通信費',
                'monthly_limit' => $monthlyLimit,
                'is_active' => true,
            ],
        ]);
    }
}
