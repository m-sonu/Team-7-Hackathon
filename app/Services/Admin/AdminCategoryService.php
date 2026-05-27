<?php

namespace App\Services\Admin;

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class AdminCategoryService
{
    public function getCategoryBillDetails(
        int $userId,
        int $categoryId,
        int $month,
        int $year,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): array {
        if ($startDate === null || $endDate === null) {
            $date = Carbon::create($year, $month, 1);
            $startDate = $date->copy()->subMonth()->day(26)->startOfDay();
            $endDate = $date->copy()->day(25)->endOfDay();
        }

        $statusOrder = [
            BillStatus::UNDER_REVIEW->value,
            BillStatus::VERIFIED->value,
            BillStatus::REJECTED->value,
            BillStatus::REIMBURSED->value,
        ];

        $bills = Bill::with(['category', 'billUploadBatch'])
            ->where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', BillStatus::adminBillStatuses())
            ->orderByRaw(
                'CASE status WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 WHEN ? THEN 4 ELSE 5 END',
                $statusOrder
            )
            ->paginate(10);

        $currency = $bills->first()?->billUploadBatch?->currency;

        $category = Category::find($categoryId);
        $categoryLimit = $category?->monthly_limit !== null ? (float) $category->monthly_limit : null;

        $approvedAmount = (float) Bill::where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', BillStatus::VERIFIED->value)
            ->sum('approve_amount');

        $updatedCategoryLimit = $categoryLimit !== null
            ? max(0.0, $categoryLimit - $approvedAmount)
            : null;

        return [
            'bills' => $bills,
            'currency' => $currency,
            'updated_category_limit' => $updatedCategoryLimit,
        ];
    }
}
