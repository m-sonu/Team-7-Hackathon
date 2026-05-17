<?php

namespace App\Services;

use App\DTOs\StoreBillDTO;
use App\Enums\AiProcessStatus;
use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\CategoryMonthlyPivot;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class BillUploadBatchService
{
    /**
     * Create a new bill upload batch.
     */
    public function createBatch(StoreBillDTO $dto): BillUploadBatch
    {
        $monthYear = $this->getMonthYear();

        // Check or create pivot
        $pivot = CategoryMonthlyPivot::query()->firstOrCreate(
            [
                'user_id' => $dto->user->id,
                'category_id' => $dto->categoryId,
                'month_year' => $monthYear,
            ],
            [
                'bill_count' => 0,
                'total_spent' => 0,
                'last_updated_at' => now(),
            ]
        );

        return BillUploadBatch::create([
            'user_id' => $dto->user->id,
            'category_id' => $dto->categoryId,
            'category_monthly_pivot_id' => $pivot->id,
            'title' => $dto->title,
            'currency' => $dto->currency,
            'ai_processing' => AiProcessStatus::PROCESSING->value,
        ]);
    }

    /**
     * Get the month-year string for the billing cycle.
     */
    public function getMonthYear(): string
    {
        $date = Carbon::now();
        $cutoff = config('app.billing_cutoff', 26);

        if ($date->day >= $cutoff) {
            $date->addMonth();
        }

        return $date->format('Y-m');
    }

    /**
     * Validate all bills in a batch and update their status in the database.
     */
    public function validateAndSetBillStatuses(BillUploadBatch $batch): void
    {
        $batch->load('bills');
        $seenInBatch = [];

        // Fetch all existing (vat_no, bill_no) pairs for this user, excluding current batch bills
        $globalDuplicates = Bill::query()
            ->where('user_id', $batch->user_id)
            ->whereNotIn('id', $batch->bills->pluck('id'))
            ->whereNotNull('vat_no')
            ->whereNotNull('bill_no')
            ->select(['vat_no', 'bill_no'])
            ->get()
            ->map(fn (Bill $b) => "{$b->vat_no}_{$b->bill_no}")
            ->flip()
            ->toArray();

        foreach ($batch->bills as $bill) {
            $isValid = true;
            $reason = null;

            // Rule 1: Data Integrity
            if (empty($bill->vat_no) || empty($bill->bill_no)) {
                $isValid = false;
                $reason = 'Missing or unreadable VAT/Bill number';
            } else {
                $key = "{$bill->vat_no}_{$bill->bill_no}";

                // Rule 3: Batch Collision
                if (isset($seenInBatch[$key])) {
                    $isValid = false;
                    $reason = 'Duplicate bill within the same batch';
                }
                // Rule 2: Global Uniqueness
                elseif (isset($globalDuplicates[$key])) {
                    $isValid = false;
                    $reason = 'Bill already exists in previous submissions';
                } else {
                    $seenInBatch[$key] = true;
                }
            }

            // Only update bills that haven't been submitted yet
            if (in_array($bill->status, [BillStatus::PENDING, BillStatus::INVALID])) {
                $bill->update([
                    'status' => $isValid ? BillStatus::PENDING : BillStatus::INVALID,
                    'reason_for_action' => $reason,
                ]);
            } elseif ($isValid) {
                $seenInBatch["{$bill->vat_no}_{$bill->bill_no}"] = true;
            }
        }
    }

    /**
     * Get the batch preview details including validation status.
     */
    public function getBatchPreview(int $id): array
    {
        $batch = BillUploadBatch::with(['bills.media', 'bills.billUploadBatch', 'user', 'category'])->findOrFail($id);

        $validBills = collect();
        $invalidBills = collect();
        $seenInBatch = [];

        // Fetch all existing (vat_no, bill_no) pairs for this user, excluding current batch bills
        $globalDuplicates = Bill::query()
            ->where('user_id', $batch->user_id)
            ->whereNotIn('id', $batch->bills->pluck('id'))
            ->whereNotNull('vat_no')
            ->whereNotNull('bill_no')
            ->select(['vat_no', 'bill_no'])
            ->get()
            ->map(fn (Bill $b) => "{$b->vat_no}_{$b->bill_no}")
            ->flip()
            ->toArray();

        foreach ($batch->bills as $bill) {
            $isValid = true;
            $reason = null;

            // Rule 1: Data Integrity
            if (empty($bill->vat_no) || empty($bill->bill_no)) {
                $isValid = false;
                $reason = 'Missing or unreadable VAT/Bill number';
            } else {
                $key = "{$bill->vat_no}_{$bill->bill_no}";

                // Rule 3: Batch Collision
                if (isset($seenInBatch[$key])) {
                    $isValid = false;
                    $reason = 'Duplicate bill within the same batch';
                }
                // Rule 2: Global Uniqueness
                elseif (isset($globalDuplicates[$key])) {
                    $isValid = false;
                    $reason = 'Bill already exists in previous submissions';
                } else {
                    $seenInBatch[$key] = true;
                }
            }

            $bill->is_valid = $isValid;
            $bill->validation_error = $reason ?? $bill->reason_for_action;
            $bill->file_preview_url = URL::signedRoute('bills.file', [
                'bill' => $bill->id,
                'user' => auth('sanctum')->id(),
            ]);

            if ($isValid) {
                $validBills->push($bill);
            } else {
                $invalidBills->push($bill);
            }
        }

        return [
            'batch' => $batch,
            'valid_bills' => $validBills,
            'invalid_bills' => $invalidBills,
            'totals' => [
                'valid' => (float) $validBills->sum('amount'),
                'invalid' => (float) $invalidBills->sum('amount'),
                'combined' => (float) $batch->bills->sum('amount'),
            ],
        ];
    }
}
