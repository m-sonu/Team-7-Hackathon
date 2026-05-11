<?php

namespace App\Actions;

use App\DTOs\AiParsedBillDTO;
use App\DTOs\StoreBillDTO;
use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use Throwable;

class StoreBillAction
{
    /**
     * @throws Throwable
     */
    public function execute(StoreBillDTO $storeBillDto, string $imagePath, AiParsedBillDTO $aiDTO, string $originalName, BillUploadBatch $batch)
    {
        // Check or create pivot
        $pivot = $batch->categoryMonthlyPivot;

        // Create bill
        $bill = Bill::query()->create(array_merge($aiDTO->bill, [
            'user_id' => $storeBillDto->user->id,
            'category_id' => $storeBillDto->categoryId,
            'category_monthly_pivot_id' => $pivot->id,
            'bill_upload_batch_id' => $batch->id,
            'status' => BillStatus::PENDING,
        ]));

        // Add media from storage
        $bill->addMediaFromDisk($imagePath)
            ->usingName($originalName)
            ->toMediaCollection('bills');

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
}
