<?php

namespace App\Services;

use App\Mail\ClaimableAmountReportMail;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BillService
{
    /**
     * Get bills with associated items and filters.
     */
    public function getFilteredBills(array $filters): LengthAwarePaginator
    {
        $query = Bill::with('items');

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
    public function changeBillStatus(Bill $bill, string $status): Bill
    {
        $bill->update(['status' => $status]);

        return $bill;
    }

    /**
     * Calculate claimable amount for a user and notify them and admins.
     */
    public function calculateAndEmailClaimableAmount(User $user): array
    {
        $month = now()->month;
        $year = now()->year;

        $bills = Bill::where('user_id', $user->id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->with(['items' => function ($query) {
                $query->where('is_claimable', true);
            }])
            ->get();

        $totalClaimableAmount = 0;
        $billsResponse = [];

        foreach ($bills as $bill) {
            $billTotal = $bill->items->sum('price');
            if ($bill->items->count() > 0) {
                $totalClaimableAmount += $billTotal;

                $billsResponse[] = [
                    'bill_id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'date' => $bill->created_at->toDateTimeString(),
                    'bill_total' => (float) $billTotal,
                    'items' => $bill->items->map(function ($item) {
                        return [
                            'item_id' => $item->id,
                            'name' => $item->name,
                            'price' => (float) $item->price,
                        ];
                    })->values()->all(),
                ];
            }
        }

        Mail::to($user->email)->send(new ClaimableAmountReportMail($user, $bills, (float) $totalClaimableAmount, $month, $year));

        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new ClaimableAmountReportMail($user, $bills, (float) $totalClaimableAmount, $month, $year));
        }

        return [
            'user_id' => $user->id,
            'month' => $month,
            'year' => $year,
            'total_claimable_amount' => (float) $totalClaimableAmount,
            'bills' => $billsResponse,
        ];
    }

    /**
     * Bulk update claim status for items.
     *
     * @return User|null The user associated with the updated items (from the first item)
     */
    public function bulkUpdateClaimStatus(array $items): ?User
    {
        $userId = DB::transaction(function () use ($items) {
            $targetUserId = null;
            foreach ($items as $item) {
                $billItem = BillItem::with('bill')->find($item['item_id']);
                if ($billItem) {
                    $billItem->update(['is_claimable' => $item['is_claimable']]);
                    if ($billItem->bill && ! $targetUserId) {
                        $targetUserId = $billItem->bill->user_id;
                    }
                }
            }

            return $targetUserId;
        });

        return $userId ? User::find($userId) : null;
    }
}
