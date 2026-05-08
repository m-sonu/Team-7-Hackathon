<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\Category;
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
                    ]
                ]
            ]);
        
        $response->assertJsonFragment([
            'title' => 'Travel Batch',
            'approved_amount' => 300, // 3 * 100
        ]);
        
        // Verify date format M d y
        $response->assertJsonFragment([
            'created_date' => $batch->created_at->format('M d y'),
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

        // May 2026 Billing cycle: April 26 to May 25
        
        // 1. Included: April 26
        BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-04-26 10:00:00',
        ]);

        // 2. Included: May 25
        BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-05-25 10:00:00',
        ]);

        // 3. Excluded: April 25
        BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-04-25 10:00:00',
        ]);

        // 4. Excluded: May 26
        BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'created_at' => '2026-05-26 10:00:00',
        ]);

        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}/bills?month=2026-05");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_employee_dashboard_returns_current_month_totals_and_category_breakdown(): void
    {
        Carbon::setTestNow('2026-05-08 12:00:00');

        $user = User::factory()->create(['currency' => 'NPR']);
        $travelCategory = Category::factory()->create(['name' => 'Travel']);
        $foodCategory = Category::factory()->create(['name' => 'Food']);

        $batchInCycle1 = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $travelCategory->id,
            'created_at' => '2026-04-26 10:00:00',
        ]);

        $batchInCycle2 = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $foodCategory->id,
            'created_at' => '2026-05-10 10:00:00',
        ]);

        $batchOutsideCycle = BillUploadBatch::factory()->create([
            'user_id' => $user->id,
            'category_id' => $travelCategory->id,
            'created_at' => '2026-05-26 10:00:00',
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'category_id' => $travelCategory->id,
            'bill_upload_batch_id' => $batchInCycle1->id,
            'approve_amount' => 100,
            'status' => Bill::STATUS_VERIFIED,
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'category_id' => $foodCategory->id,
            'bill_upload_batch_id' => $batchInCycle2->id,
            'approve_amount' => 200,
            'status' => Bill::STATUS_PENDING,
        ]);

        Bill::factory()->create([
            'user_id' => $user->id,
            'category_id' => $travelCategory->id,
            'bill_upload_batch_id' => $batchOutsideCycle->id,
            'approve_amount' => 300,
            'status' => Bill::STATUS_VERIFIED,
        ]);

        $response = $this->actingAs($user)->getJson('/api/user/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_bills', 3)
            ->assertJsonPath('data.total_approved_amount', 'रु 600')
            ->assertJsonPath('data.current_month_total_approved_amount', 'रु 300')
            ->assertJsonPath('data.current_month_verified_bills', 1)
            ->assertJsonCount(2, 'data.category_wise_amounts');

        $response->assertJsonFragment([
            'category' => 'Travel',
            'approved_amount' => 'रु 100',
            'bill_count' => 1,
        ]);

        $response->assertJsonFragment([
            'category' => 'Food',
            'approved_amount' => 'रु 200',
            'bill_count' => 1,
        ]);

        Carbon::setTestNow();
    }

    public function test_user_gets_pending_priority(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Batch 1: Verified
        $batch1 = BillUploadBatch::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'title' => 'Verified Batch']);
        Bill::factory()->create(['bill_upload_batch_id' => $batch1->id, 'status' => Bill::STATUS_VERIFIED]);

        // Batch 2: Pending
        $batch2 = BillUploadBatch::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'title' => 'Pending Batch']);
        Bill::factory()->create(['bill_upload_batch_id' => $batch2->id, 'status' => Bill::STATUS_PENDING]);

        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}/bills");

        $response->assertStatus(200);
        $this->assertEquals('Pending Batch', $response->json('data.0.title'));
    }
}
