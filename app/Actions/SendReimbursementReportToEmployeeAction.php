<?php

declare(strict_types=1);

namespace App\Actions;

use App\Mail\EmployeeReimbursementReportMailable;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendReimbursementReportToEmployeeAction
{
    /**
     * Send a reimbursement report email to the employee.
     *
     * @param  Collection<int, Bill>  $reimbursedBills
     */
    public function execute(User $employee, Collection $reimbursedBills, string $monthYear, string $currency): void
    {
        $totalRequestedAmount = (float) $reimbursedBills->sum('amount');
        $totalReimbursedAmount = (float) $reimbursedBills->sum('approve_amount');

        $humanReadableMonthYear = Carbon::createFromFormat('Y-m', $monthYear)->format('F Y');

        try {
            Mail::to($employee->email)->queue(
                new EmployeeReimbursementReportMailable(
                    employee: $employee,
                    bills: $reimbursedBills,
                    totalRequestedAmount: $totalRequestedAmount,
                    totalReimbursedAmount: $totalReimbursedAmount,
                    monthYear: $humanReadableMonthYear,
                    currency: $currency,
                )
            );
        } catch (Throwable $e) {
            Log::error('Failed to queue reimbursement report email to employee.', [
                'employee_id' => $employee->id,
                'month_year' => $monthYear,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
