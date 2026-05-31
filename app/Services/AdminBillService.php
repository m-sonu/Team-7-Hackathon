<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminBillService
{
    /**
     * Fetch paginated employee users with bill aggregates for the given date range and optional status filter.
     *
     * Currency cannot be meaningfully aggregated per user because a single user may have bills
     * across batches with different currencies. The currency is intentionally omitted here;
     * callers should resolve it per-batch via the BillUploadBatch relationship when needed.
     */
    public function getEmployeeBillsSummary(
        Carbon $startDate,
        Carbon $endDate,
        ?BillStatus $status,
        int $perPage,
    ): LengthAwarePaginator {
        $billFilter = function ($query) use ($startDate, $endDate, $status): void {
            $query->whereBetween('created_at', [$startDate, $endDate->endOfDay()]);

            if ($status) {
                $query->where('status', $status->value);
            }
            else{
                $query->whereIn('status', BillStatus::adminBillStatuses());
            }
        };

        return User::query()
            ->with('bills.billUploadBatch:id,currency')
            ->where('role', UserRole::EMPLOYEE->value)
            ->whereHas('bills', $billFilter)
            ->withCount(['bills as bills_count' => $billFilter])
            ->withSum(['bills as total_amount' => $billFilter], 'amount')
            ->withSum(['bills as total_approve_amount' => $billFilter], 'approve_amount')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getCategoryWiseBillsForUser(
        int $userId,
        int $month,
        int $year,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?string $status = null,
    ): Collection {
        if ($startDate === null || $endDate === null) {
            $date = Carbon::create($year, $month, 1);
            $startDate = $date->copy()->subMonth()->day(26)->startOfDay();
            $endDate = $date->copy()->day(25)->endOfDay();
        }

        $validStatuses = $status? [$status]:BillStatus::adminBillStatuses();
        logger($status,$validStatuses);
        $statusList = implode("','", $validStatuses);

        return DB::table('bill')
            ->where('bill.user_id', $userId)
            ->whereIn('bill.status', $validStatuses)
            ->join('category', 'category.id', '=', 'bill.category_id')
            ->leftJoin('bill_upload_batch as batch', function ($join) use ($userId, $startDate, $endDate) {
                $join->on('batch.id', '=', DB::raw(
                    "(SELECT b2.bill_upload_batch_id
                  FROM bill b2
                  WHERE b2.category_id = bill.category_id
                    AND b2.user_id = {$userId}
                    AND b2.created_at BETWEEN '{$startDate}' AND '{$endDate}'
                  ORDER BY b2.created_at DESC
                  LIMIT 1)"
                ));
            })
            ->selectRaw("
            MAX(bill.updated_at)         AS updated_at,
            bill.category_id,
            category.name                AS category_name,
            category.jp_name             AS category_jp_name,
            COUNT(bill.id)               AS bill_count,
            SUM(CASE WHEN bill.status IN ('{$statusList}')
                THEN bill.amount ELSE 0 END)         AS total_amount,
            SUM(bill.approve_amount)     AS approved_amount,
            MAX(batch.currency)          AS currency,
            (
                SELECT b2.status
                FROM bill b2
                WHERE b2.category_id = bill.category_id
                  AND b2.user_id = ?
                  AND b2.created_at BETWEEN ? AND ?
                ORDER BY
                    CASE b2.status
                        WHEN 'reimbursed'   THEN 1
                        WHEN 'rejected'     THEN 2
                        WHEN 'verified'     THEN 3
                        WHEN 'under review' THEN 4
                        ELSE 0
                    END DESC
                LIMIT 1
            ) AS highest_status
        ", [$userId, $startDate, $endDate])
            ->groupBy('bill.category_id', 'category.name', 'category.jp_name', 'batch.currency')
            ->get();
    }
}
