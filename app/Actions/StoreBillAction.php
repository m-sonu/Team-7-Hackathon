<?php

namespace App\Actions;

use App\DTOs\AiParsedBillDTO;
use App\Models\Bill;
use App\Models\CategoryMonthlyPivot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class StoreBillAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, int $categoryId, string $imagePath, AiParsedBillDTO $aiDTO)
    {
        return DB::transaction(function () use ($user, $categoryId, $imagePath, $aiDTO) {
            $monthYear = now()->format('Y-m');

            // Check or create pivot
            $pivot = CategoryMonthlyPivot::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $categoryId,
                    'month_year' => $monthYear,
                ],
                [
                    'bill_count' => 0,
                    'total_spent' => 0,
                    'last_updated_at' => now(),
                ]
            );

            // Create bill
            $bill = Bill::create(array_merge($aiDTO->bill, [
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'category_monthly_pivot_id' => $pivot->id,
                'status' => Bill::STATUS_PENDING,
            ]));

            // Add media from storage
            $bill->addMediaFromDisk($imagePath)->toMediaCollection('bills');


            // Create vendor contact
            if (! empty($aiDTO->vendorContact)) {
                $bill->vendorContact()->create($aiDTO->vendorContact);
            }

            // Update pivot stats
            $pivot->increment('bill_count');
            $pivot->increment('total_spent', $aiDTO->amount ?? 0);
            $pivot->update(['last_updated_at' => now()]);

            return $bill->load('vendorContact');
        });
    }
}
