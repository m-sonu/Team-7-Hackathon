<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin User one',
            'email' => 'admin1@jobins.com',
            'password' => Hash::make('Admin@123#'),
            'role' => 'Admin',
        ]);

        User::create([
            'name' => 'Admin User two',
            'email' => 'admin2@jobins.com',
            'password' => Hash::make('Admin@123#'),
            'role' => 'Admin',
        ]);
    }
}
