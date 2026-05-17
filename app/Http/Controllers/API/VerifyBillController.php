<?php

namespace App\Http\Controllers\API;

use App\Enums\BillStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\VerifyBillRequest;
use App\Jobs\BulkReimburseBillsJob;
use App\Models\Bill;
use App\Models\CategoryMonthlyPivot;
use App\Services\BillUploadBatchService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class VerifyBillController extends Controller
{
    /**
     * Verify or reject an individual bill.
     */
    public function verifyBill(VerifyBillRequest $request, Bill $bill): JsonResponse
    {
          $bill->load('billUploadBatch', 'category');

    $currency = $bill->billUploadBatch?->currency ?? 'YEN';

    $baseResponse = [
        'success' => true,
        'remaining_category_amount' => format_currency(0, $currency),
        'already_approved' => format_currency(0, $currency),
        'total_amount' => format_currency($bill->amount, $currency),
        'currency' => $currency,
        'final_approve_amount' => format_currency(0, $currency),
    ];

    if ($bill->status) {
        return response()->json([
            ...$baseResponse,
            'message' => 'Bill has already been verified',
            'approve_amount' => format_currency($bill->approve_amount, $currency),
        ], 200);
    }

    if (in_array($request->status, [
        BillStatus::INVALID->value,
        BillStatus::REJECTED->value,
    ])) {

        $bill->update([
            'status' => BillStatus::REJECTED->value,
            'approve_amount' => 0,
            'reason_for_action' => $request->reason_for_action,
        ]);

        return response()->json([
            ...$baseResponse,
            'message' => 'Bill has already been verified or rejected.',
            'approve_amount' => format_currency(0, $currency),
        ], 200);
    }

    $bill->update([
        'status' => $request->status,
        'approve_amount' => $bill->amount,
        'reason_for_action' => $request->reason_for_action ?? '',
    ]);

    return response()->json([
        ...$baseResponse,
        'message' => "Bill has been {$request->status}.",
        'approve_amount' => format_currency($bill->amount, $currency),
    ], 200);
    }

    /**
     * Bulk reimburse all verified bills for a specific category monthly pivot.
     * Admin is only allowed to perform this action for the current billing month.
     */
    public function bulkReimburse(int $pivotId, BillUploadBatchService $service): JsonResponse
    {
        $pivot = CategoryMonthlyPivot::findOrFail($pivotId);
        $currentMonthYear = $service->getMonthYear();
        if ($pivot->month_year != $currentMonthYear) {
            return response()->json([
                'success' => false,
                'message' => 'Action only allowed for the current billing month.',
            ], 403);
        }

        BulkReimburseBillsJob::dispatch($pivotId);

        return response()->json([
            'success' => true,
            'message' => 'Bulk reimbursement process has been queued.',
        ]);
    }
}
