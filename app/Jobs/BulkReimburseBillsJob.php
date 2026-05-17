<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SendReimbursementReportToEmployeeAction;
use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BulkReimburseBillsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $categoryMonthlyIds) {}

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(): void
    {
        DB::transaction(function () {
            Bill::query()
                ->where('status', BillStatus::VERIFIED)
                ->whereIn('category_monthly_pivot_id', $this->categoryMonthlyIds)
                ->update(['status' => BillStatus::REIMBURSED]);
        });

        try {
            $reimbursedBills = Bill::query()
                ->where('status', BillStatus::REIMBURSED)
                ->whereIn('category_monthly_pivot_id', $this->categoryMonthlyIds)
                ->with(['category', 'vendorContact', 'billUploadBatch.categoryMonthlyPivot'])
                ->get();

            $reimbursedBills
                ->groupBy('user_id')
                ->each(function ($userBills, int $userId): void {
                    $employee = User::query()->find($userId);

                    if (! $employee) {
                        Log::warning('Skipping reimbursement report: employee not found.', [
                            'user_id' => $userId,
                            'pivot_ids' => $this->categoryMonthlyIds,
                        ]);

                        return;
                    }

                    $firstBatch = $userBills->first()->billUploadBatch;
                    $monthYear = $firstBatch->categoryMonthlyPivot->month_year;
                    $currency = $firstBatch->currency;

                    (new SendReimbursementReportToEmployeeAction)->execute(
                        employee: $employee,
                        reimbursedBills: $userBills,
                        monthYear: $monthYear,
                        currency: $currency,
                    );
                });
        } catch (Throwable $e) {
            Log::error('Failed to dispatch reimbursement report emails after bulk reimburse.', [
                'pivot_ids' => $this->categoryMonthlyIds,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
