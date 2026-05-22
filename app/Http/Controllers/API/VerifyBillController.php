<?php

namespace App\Http\Controllers\API;

use App\Enums\BillStatus;
use App\Http\Controllers\ApiController;
use App\Http\Requests\API\VerifyBillRequest;
use App\Jobs\BulkReimburseBillsJob;
use App\Models\Bill;
use App\Models\CategoryMonthlyPivot;
use App\Services\BillUploadBatchService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBillController extends ApiController
{
    /**
     * Verify or reject an individual bill.
     */
    public function verifyBill(VerifyBillRequest $request, Bill $bill): JsonResponse
    {
        $bill->load('billUploadBatch', 'category');

        $currency = $bill->billUploadBatch?->currency ?? 'YEN';

        $baseData = [
            'remaining_category_amount' => format_currency(0, $currency),
            'already_approved' => format_currency(0, $currency),
            'total_amount' => format_currency($bill->amount, $currency),
            'currency' => $currency,
            'final_approve_amount' => format_currency(0, $currency),
        ];

        $settledStatuses = [BillStatus::VERIFIED->value, BillStatus::REJECTED->value, BillStatus::REIMBURSED->value];
        if (in_array($bill->status, $settledStatuses)) {
            return $this->sendResponse(
                array_merge($baseData, ['approve_amount' => format_currency($bill->approve_amount, $currency)]),
                'Bill has already been verified or rejected.'
            );
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

            return $this->sendResponse(
                array_merge($baseData, ['approve_amount' => format_currency(0, $currency)]),
                'Bill has already been verified or rejected.'
            );
        }

        $bill->update([
            'status' => $request->status,
            'approve_amount' => $bill->amount,
            'reason_for_action' => $request->reason_for_action ?? '',
        ]);

        return $this->sendResponse(
            array_merge($baseData, ['approve_amount' => format_currency($bill->amount, $currency)]),
            "Bill has been {$request->status}."
        );
    }

    /**
     * Bulk reimburse all verified bills for a specific category monthly pivot.
     * Admin is only allowed to perform this action for the current billing month.
     */
    public function bulkReimburse(BillUploadBatchService $service): JsonResponse
    {
        $categoryMonthlyIds = CategoryMonthlyPivot::query()->where('month_year', Carbon::now()->format('Y-m'))->pluck('id');

        if ($categoryMonthlyIds->isEmpty()) {
            return $this->sendError('Action only allowed for the current billing month.', Response::HTTP_FORBIDDEN);
        }

        $billsExits = Bill::query()->whereIn('category_monthly_pivot_id', $categoryMonthlyIds->toArray())->where('status', BillStatus::VERIFIED->value);
        if (! $billsExits->exists()) {
            return $this->sendError('There are no bills to reimburse.', Response::HTTP_BAD_REQUEST);
        }

        BulkReimburseBillsJob::dispatch($categoryMonthlyIds->toArray());

        return $this->sendResponse([], 'Bulk reimbursement process has been queued.');
    }

    /**
     * Reimburse all verified bills for a specific category monthly pivot.
     */
    public function reimburseByPivot(Request $request): JsonResponse
    {
        $table=CategoryMonthlyPivot::TABLE_NAME;
        $request->validate([
            'pivot_id' => ['required', 'integer', "exists:{$table},id"],
        ]);

        $pivotId = $request->integer('pivot_id');

        $hasVerifiedBills = Bill::query()
            ->where('category_monthly_pivot_id', $pivotId)
            ->where('status', BillStatus::VERIFIED->value)
            ->exists();

        if (! $hasVerifiedBills) {
            return $this->sendError('There are no verified bills to reimburse.', Response::HTTP_BAD_REQUEST);
        }

        BulkReimburseBillsJob::dispatch([$pivotId]);

        return $this->sendResponse([], 'Reimbursement process has been queued.');
    }
}
