<?php

namespace App\Actions;

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmitBillForReimburseAction
{
    /**
     * Submit all pending bills in a batch for reimbursement (marks them as UNDER_REVIEW).
     *
     * @return Collection<int, Bill>
     */
    public function execute(BillUploadBatch $batch): Collection
    {
        $bills = $batch->bills()
            ->where('status', BillStatus::PENDING)
            ->with('vendorContact')
            ->get();

        DB::transaction(function () use ($batch) {
            $batch->bills()
                ->where('status', BillStatus::PENDING)
                ->update(['status' => BillStatus::UNDER_REVIEW]);
        });

        return $bills;
    }
}
