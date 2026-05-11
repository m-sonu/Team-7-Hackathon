<?php

namespace App\Jobs;

use App\Enums\BillStatus;
use App\Models\Bill;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class BulkReimburseBillsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $pivotId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            Bill::query()
                ->where('status', BillStatus::VERIFIED)
                ->where('category_monthly_pivot_id', $this->pivotId)
                ->update(['status' => BillStatus::REIMBURSED]);
        });
    }
}
