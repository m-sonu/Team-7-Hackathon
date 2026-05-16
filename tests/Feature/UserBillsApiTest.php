<?php

namespace Tests\Feature;

use App\Enums\AiProcessStatus;
use App\Enums\BillStatus;
use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\Category;
use App\Models\CategoryMonthlyPivot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserBillsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_their_bills(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Travel']);

        $batch = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Travel Batch',
            'currency' => 'USD',
            'ai_processing'=> AiProcessStatus::SUCCESS->value
        ]);

        Bill::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'bill_upload_batch_id' => $batch->id,
            'approve_amount' => 100,
        ]);

        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}/bills");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'category',
                        'created_date',
                        'approved_amount',
                        'status',
                    ],
                ],
            ]);

        $response->assertJsonFragment([
            'title' => 'Travel Batch',
            'approved_amount' => '$ 300',
        ]);

        $response->assertJsonFragment([
            'created_date' => $batch->created_at->format('M d Y'),
        ]);
    }

    public function test_user_can_filter_bills_by_category(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['name' => 'Travel']);
        $category2 = Category::factory()->create(['name' => 'Food']);

        $batch1 = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'Travel Batch',
            'ai_processing'=> AiProcessStatus::SUCCESS->value
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'bill_upload_batch_id' => $batch1->id,
        ]);

        $batch2 = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'title' => 'Food Batch',
            'ai_processing'=> AiProcessStatus::SUCCESS->value
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'bill_upload_batch_id' => $batch2->id,
        ]);

        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}/bills?category_id={$category1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'Travel');
    }

    public function test_user_can_filter_bills_by_month_billing_cycle(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // May 2026 billing cycle: April 26 to May 25

        // 1. Included: April 26
        $batch1 = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-04-26 10:00:00',
            'ai_processing'=> AiProcessStatus::SUCCESS->value
        ]);
        Bill::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'bill_upload_batch_id' => $batch1->id]);

        // 2. Included: May 25
        $batch2 = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-05-25 10:00:00',
            'ai_processing'=> AiProcessStatus::SUCCESS->value
        ]);
        Bill::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'bill_upload_batch_id' => $batch2->id]);

        // 3. Excluded: April 25 (before cycle start)
        $batch3 = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-04-25 10:00:00',
            'ai_processing'=> AiProcessStatus::SUCCESS->value
        ]);
        Bill::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'bill_upload_batch_id' => $batch3->id]);

        // 4. Excluded: May 26 (after cycle end)
        $batch4 = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-05-26 10:00:00',
            'ai_processing'=> AiProcessStatus::SUCCESS->value
        ]);
        Bill::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'bill_upload_batch_id' => $batch4->id]);

        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}/bills?month=2026-05");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_employee_dashboard_returns_current_month_totals_and_category_breakdown(): void
    {
        Carbon::setTestNow('2026-05-08 12:00:00');

        $user = User::factory()->create(['role' => UserRole::EMPLOYEE]);
        $travelCategory = Category::factory()->create(['name' => 'Travel']);
        $foodCategory = Category::factory()->create(['name' => 'Food']);

        // Billing cycle month = '2026-05' (day 8 < cutoff 26)
        $travelPivot = CategoryMonthlyPivot::factory()->create([
            'user_id' => $user->id,
            'category_id' => $travelCategory->id,
            'month_year' => '2026-05',
        ]);
        $foodPivot = CategoryMonthlyPivot::factory()->create([
            'user_id' => $user->id,
            'category_id' => $foodCategory->id,
            'month_year' => '2026-05',
        ]);

        $batchTravel = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $travelCategory->id,
            'category_monthly_pivot_id' => $travelPivot->id,
            'ai_processing' => AiProcessStatus::SUCCESS->value,
        ]);
        $batchFood = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $foodCategory->id,
            'category_monthly_pivot_id' => $foodPivot->id,
            'ai_processing'=> AiProcessStatus::SUCCESS->value
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'category_id' => $travelCategory->id,
            'bill_upload_batch_id' => $batchTravel->id,
            'category_monthly_pivot_id' => $travelPivot->id,
            'approve_amount' => 100,
            'status' => BillStatus::VERIFIED,
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'category_id' => $foodCategory->id,
            'bill_upload_batch_id' => $batchFood->id,
            'category_monthly_pivot_id' => $foodPivot->id,
            'approve_amount' => 200,
            'status' => BillStatus::PENDING,
        ]);

        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}/dashboard");

        $response->assertStatus(200)
            ->assertJsonPath('total_bills', 2)
            ->assertJsonPath('current_month_verified_bills', 1)
            ->assertJsonCount(2, 'category_wise_amounts');

        $response->assertJsonFragment(['category' => 'Travel', 'bill_count' => 1]);
        $response->assertJsonFragment(['category' => 'Food', 'bill_count' => 1]);

        Carbon::setTestNow();
    }

    public function test_user_gets_pending_priority(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Batch 1: Verified (older — appears second in orderByDesc)
        $batch1 = BillUploadBatch::factory()->create(
            ['user_id' => $user->id,
                'category_id' => $category->id,
                'title' => 'Verified Batch',
                'created_at' => now()->subMinute(),
                'ai_processing'=> AiProcessStatus::SUCCESS->value]);
        Bill::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'bill_upload_batch_id' => $batch1->id, 'status' => BillStatus::VERIFIED]);

        // Batch 2: Pending (newer — appears first in orderByDesc)
        $batch2 = BillUploadBatch::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'title' => 'Pending Batch', 'created_at' => now(), 'ai_processing' => AiProcessStatus::SUCCESS->value]);
        Bill::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'bill_upload_batch_id' => $batch2->id, 'status' => BillStatus::PENDING]);

        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}/bills");

        $response->assertStatus(200);
        $this->assertEquals('Pending Batch', $response->json('data.0.title'));
    }
}
