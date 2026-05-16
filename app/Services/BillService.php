<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Mail\ClaimableAmountReportMail;
use App\Models\Bill;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class BillService
{
    /**
     * Get bills with associated filters.
     */
    public function getFilteredBills(array $filters): LengthAwarePaginator
    {
        $query = Bill::query();

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('created_at', [
                $filters['start_date'].' 00:00:00',
                $filters['end_date'].' 23:59:59',
            ]);
        }

        return $query->paginate(15);
    }

    /**
     * Change the status of a bill.
     */
    public function changeBillStatus(Bill $bill, BillStatus $status): Bill
    {
        $bill->update(['status' => $status]);

        return $bill;
    }

    /**
     * Calculate claimable amount for a user and notify them and admins.
     */
    public function calculateAndEmailClaimableAmount(User $user): array
    {
        $date = Carbon::now();
        $month = $date->month;
        $year = $date->year;
        $startDate = $date->copy()->subMonth()->day(26)->startOfDay();
        $endDate = $date->copy()->day(25)->endOfDay();

        // For now, we consider all verified bills as claimable
        $bills = Bill::with(['category','billUploadBatch'])->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->where('status', BillStatus::VERIFIED)
            ->get();
        $totalClaimableAmount = format_currency($bills->sum('amount'), $bills->first()->billUploadBatch->currency ?? '');
        $totalApproveAmount = format_currency($bills->sum('approve_amount'), $bills->first()->billUploadBatch->currency ?? '');
        $billsResponse = [];
        foreach ($bills as $bill) {
            $billsResponse[] = [
                'bill_id' => $bill->id,
                'bill_number' => $bill->bill_no,
                'date' => $bill->created_at->format('M d Y'),
                'bill_total' => format_currency($bill->amount, $bill->billUploadBatch->currency ?? ''),
                'bill_approved' => format_currency($bill->approve_amount, $bill->billUploadBatch->currency ?? ''),
            ];
        }

        Mail::to($user->email)->send(new ClaimableAmountReportMail($user, $bills, $totalClaimableAmount, $totalApproveAmount, $month, $year));

        $admins = User::where('role', UserRole::ADMIN->value)->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new ClaimableAmountReportMail($user, $bills, $totalClaimableAmount, $totalApproveAmount, $month, $year));
        }

        return [
            'success' => true,
            'message' => 'Claimable amount calculated successfully',
            'user_id' => $user->id,
            'month' => $month,
            'year' => $year,
            'total_claimable_amount' => $totalClaimableAmount,
            'total_approved_amount' => $totalApproveAmount,
            'data' => $billsResponse,
        ];
    }
}
