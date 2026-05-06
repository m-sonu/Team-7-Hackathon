<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\Category;
use App\Models\CategoryMonthlyPivot;
use App\Models\User;
use App\Models\VendorContact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CategoryMonthlyPivotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assuming 'Employee' is the normal role based on UserSeeder
        $users = User::where('role', 'Employee')->get();
        $categories = Category::all();

        if ($users->isEmpty()) {
            $this->command->info('No users with "Employee" role found. Please run UserSeeder first.');

            return;
        }

        if ($categories->isEmpty()) {
            $this->command->info('No categories found. Please run CategorySeeder first.');

            return;
        }

        foreach ($users as $user) {
            $monthsCount = rand(3, 5);
            $this->command->info("Seeding data for user: {$user->name} for {$monthsCount} months.");

            for ($i = 0; $i < $monthsCount; $i++) {
                $date = Carbon::now()->subMonths($i);
                $monthYear = $date->format('Y-m');

                foreach ($categories as $category) {
                    // Create the pivot record
                    $pivot = CategoryMonthlyPivot::create([
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'month_year' => $monthYear,
                        'bill_count' => 1,
                        'total_spent' => 0, // Will be updated
                        'last_updated_at' => now(),
                    ]);

                    // Create a bill upload batch
                    $batch = BillUploadBatch::create([
                        'title' => "Bills for {$category->name} - {$monthYear}",
                        'currency' => 'NRP',
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'category_monthly_pivot_id' => $pivot->id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    // Generate a random amount
                    $amount = rand(500, 5000);

                    // Create the bill
                    $bill = Bill::factory()->create([
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'category_monthly_pivot_id' => $pivot->id,
                        'bill_upload_batch_id' => $batch->id,
                        'amount' => $amount,
                        'approve_amount' => $amount,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    $pivot->update([
                        'total_spent' => $amount,
                    ]);

                    // Create vendor information
                    VendorContact::factory()->create([
                        'bill_id' => $bill->id,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }
    }
}
