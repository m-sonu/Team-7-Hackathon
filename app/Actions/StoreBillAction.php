<?php

namespace App\Actions;

use App\DTOs\AiParsedBillDTO;
use App\DTOs\StoreBillDTO;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\CategoryMonthlyPivot;
use Carbon\Carbon;
use Throwable;

class StoreBillAction
{
    /**
     * @throws Throwable
     */
    public function execute(StoreBillDTO $storeBillDto, string $imagePath, AiParsedBillDTO $aiDTO)
    {
        $monthYear = $this->getMonthYear();

        // Check or create pivot
        $pivot = CategoryMonthlyPivot::query()->firstOrCreate(
            [
                'user_id' => $storeBillDto->user->id,
                'category_id' => $storeBillDto->categoryId,
                'month_year' => $monthYear,
            ],
            [
                'bill_count' => 0,
                'total_spent' => 0,
                'last_updated_at' => now(),
            ]
        );

        // Create batch
        $batch = BillUploadBatch::query()->firstOrCreate(
            [
                'user_id' => $storeBillDto->user->id,
                'category_id' => $storeBillDto->categoryId,
                'category_monthly_pivot_id' => $pivot->id,
                'title' => $storeBillDto->title,
                'currency' => $storeBillDto->currency,
            ]
        );

        // Create bill
        $bill = Bill::create(array_merge($aiDTO->bill, [
            'user_id' => $storeBillDto->user->id,
            'category_id' => $storeBillDto->categoryId,
            'category_monthly_pivot_id' => $pivot->id,
            'bill_upload_batch_id' => $batch->id,
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
    }

    private function getMonthYear(): string
    {
        $date = Carbon::now();
        $cutoff = config('app.billing_cutoff', 26);

        if ($date->day >= $cutoff) {
            $date->addMonth();
        }

        return $date->format('Y-m');
    }
}
