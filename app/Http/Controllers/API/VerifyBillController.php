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
        $bill->update([
            'status' => $request->status,
            'approve_amount' => $request->approve_amount ?? $bill->approve_amount,
            'reason_for_action' => $request->reason_for_action,
        ]);

        return response()->json([
            'message' => "Bill has been {$request->status}.",
            'data' => $bill,
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
