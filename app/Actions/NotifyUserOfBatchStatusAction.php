<?php

namespace App\Actions;

use App\Mail\BatchStatusMail;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use Illuminate\Support\Facades\Mail;

class NotifyUserOfBatchStatusAction
{
    /**
     * Process the batch validation and notify the user.
     */
    public function execute(BillUploadBatch $batch): void
    {
        $batch->load(['bills.media', 'user', 'category']);

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
            // Rule 1: Data Integrity
            if (empty($bill->vat_no) || empty($bill->bill_no)) {
                $bill->validation_error = 'Missing or unreadable VAT/Bill number';
                $invalidBills->push($bill);

                continue;
            }

            $key = "{$bill->vat_no}_{$bill->bill_no}";

            // Rule 3: Batch Collision
            if (isset($seenInBatch[$key])) {
                $bill->validation_error = 'Duplicate bill within the same batch';
                $invalidBills->push($bill);

                continue;
            }

            // Rule 2: Global Uniqueness
            if (isset($globalDuplicates[$key])) {
                $bill->validation_error = 'Bill already exists in previous submissions';
                $invalidBills->push($bill);

                continue;
            }

            $seenInBatch[$key] = true;
            $validBills->push($bill);
        }

        // Send consolidated email
        Mail::to($batch->user->email)->send(
            new BatchStatusMail($batch, $validBills, $invalidBills)
        );
    }
}
