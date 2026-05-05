<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->insert([
            [
                'name' => 'Admin User one',
                'email' => 'admin1@jobins.com',
                'password' => Hash::make('Admin@123#'),
                'role' => 'Admin',
            ],
            [
                'name' => 'Admin User two',
                'email' => 'admin2@jobins.com',
                'password' => Hash::make('Admin@123#'),
                'role' => 'Admin',
            ],
            [
                'name' => 'Employee1',
                'email' => 'employee1@jobins.com',
                'password' => Hash::make('Admin@123#'),
                'role' => 'Employee',
            ],
            [
                'name' => 'Employee2',
                'email' => 'employee2@jobins.com',
                'password' => Hash::make('Admin@123#'),
                'role' => 'Employee',
            ]
        ]);
    }
}
