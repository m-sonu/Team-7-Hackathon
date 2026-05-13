<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\VerifyBillRequest;
use App\Jobs\BulkReimburseBillsJob;
use App\Models\Bill;
use App\Models\CategoryMonthlyPivot;
use App\Services\BillUploadBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyBillController extends Controller
{
    /**
     * Verify or reject an individual bill.
     */
    public function verifyBill(VerifyBillRequest $request, Bill $bill): JsonResponse
    {
         $bill->load('billUploadBatch', 'category');

         if(in_array($bill->status, [BillStatus::INVALID->value, BillStatus::REJECTED->value])){
            $bill->update([
                'status' => BillStatus::REJECTED->value,
                'approve_amount' => 0,
                'reason_for_action' => $request->reason_for_action,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Bill has already been verified or rejected.',
            ], 422);
        }

        $date = Carbon::now();
        $startDate = $date->copy()->subMonth()->day(26)->startOfDay();
        $endDate = $date->copy()->day(25)->endOfDay();

        $categoryLimit = $bill->category->limit;
        $alreadyApproved = Bill::where('user_id', $bill->user_id)
            ->where('category_id', $bill->category_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('id', '!=', $bill->id)
            ->sum('approve_amount');

        $remainingLimit = max($categoryLimit - $alreadyApproved, 0);
        if ($remainingLimit <= 0) {
            $bill->update([
                'status' => BillStatus::REJECTED->value,
                'approve_amount' => 0,
                'reason_for_action' => 'Category limit reached',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bill limit is reached.',
                'remaining_category_amount' => format_currency(0, $currency),
            ], 422);
        
        }
        $requestedAmount = $request->approve_amount ?? 0;
        $finalApprovedAmount = min($requestedAmount, $remainingLimit);

        $bill->update([
            'status' => $request->status,
            'approve_amount' => $finalApprovedAmount,
            'reason_for_action' => $request->reason_for_action,
        ]);

        $currency = $bill->billUploadBatch?->currency ?? '';

        return response()->json([
            'message' => "Bill has been {$request->status}.",
            'currency' => $currency,
            'final_approve_amount' => format_currency($finalApprovedAmount,$currency),
            'remaining_category_amount' => format_currency(max($remainingLimit - $finalApprovedAmount, 0),$currency)
        ]);
    }

    /**
     * Bulk reimburse all verified bills for a specific category monthly pivot.
     * Admin is only allowed to perform this action for the current billing month.
     */
    public function bulkReimburse(int $pivotId, BillUploadBatchService $service): JsonResponse
    {
        $pivot = CategoryMonthlyPivot::findOrFail($pivotId);
        $currentMonthYear = $service->getMonthYear();

        if ($pivot->month_year !== $currentMonthYear) {
            return response()->json([
                'message' => 'Action only allowed for the current billing month.',
            ], 403);
        }

        BulkReimburseBillsJob::dispatch($pivotId);

        return response()->json([
            'message' => 'Bulk reimbursement process has been queued.',
        ]);
    }
}
