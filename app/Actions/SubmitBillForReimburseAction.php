<?php

namespace App\Actions;

use App\Enums\BillStatus;
use App\Models\BillUploadBatch;
use Illuminate\Support\Facades\DB;

class SubmitBillForReimburseAction
{
    /**
     * Submit all pending bills in a batch for reimbursement (marks them as UNDER_REVIEW).
     */
    public function execute(BillUploadBatch $batch): int
    {
        return DB::transaction(function () use ($batch) {
            return $batch->bills()
                ->where('status', BillStatus::PENDING)
                ->update(['status' => BillStatus::UNDER_REVIEW]);
        });
    }
}
