<?php

namespace App\Http\Controllers\API;

use App\Actions\SendReimbursementNotificationToAdmin;
use App\Actions\SubmitBillForReimburseAction;
use App\DTOs\StoreBillDTO;
use App\Enums\BillStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillStatusRequest;
use App\Jobs\ProcessBillAiJob;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\User;
use App\Services\BillService;
use App\Services\BillUploadBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BillController extends Controller
{
    public function __construct(protected BillService $billService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $bills = $this->billService->getFilteredBills($request->all());

        return response()->json([
            'success' => true,
            'data' => $bills,
        ]);
    }

    public function store(StoreBillRequest $request, BillUploadBatchService $batchService): JsonResponse
    {
        $dto = StoreBillDTO::fromRequest($request);

        $batch = $batchService->createBatch($dto);

        ProcessBillAiJob::dispatch($dto, $batch);

        return response()->json([
            'message' => 'Bills have been queued for AI processing.',
            'batch_id' => $batch->id,
            'title' => $batch->title,
        ], 202);
    }

    /**
     * Submit all pending bills in a batch for reimbursement.
     */
    public function submitBatch(
        BillUploadBatch $batch,
        SubmitBillForReimburseAction $submitAction,
        SendReimbursementNotificationToAdmin $notifyAction,
    ): JsonResponse {
        $this->authorize('view', $batch);

        $bills = $submitAction->execute($batch);

        if ($bills->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "There are no bills to send reimbursement notification for {$batch->id}",
            ], Response::HTTP_BAD_REQUEST);
        }

        $notifyAction->execute(
            requester: $batch->user,
            bills: $bills,
            batchId: $batch->id
        );

        return response()->json([
            'success' => true,
            'message' => "Successfully submitted {$bills->count()} bills for reimbursement.",
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update bill fields (bill_no, vat_no, amount) and re-validate the batch.
     */
    public function update(Request $request, Bill $bill, BillUploadBatchService $batchService): JsonResponse
    {
        if ($bill->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'bill_no' => 'nullable|string|max:255',
            'vat_no'  => 'nullable|string|max:255',
            'amount'  => 'nullable|numeric|min:0',
        ]);

        $bill->update($validated);
        $batchService->validateAndSetBillStatuses($bill->billUploadBatch);

        return response()->json([
            'success' => true,
            'message' => 'Bill updated successfully.',
        ]);
    }

    /**
     * Delete a bill (owner only).
     */
    public function destroy(Bill $bill): JsonResponse
    {
        if ($bill->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bill removed successfully.',
        ]);
    }

    /**
     * Update the status of the specified bill.
     */
    public function changeStatus(UpdateBillStatusRequest $request, Bill $bill): JsonResponse
    {
        $bill = $this->billService->changeBillStatus($bill, BillStatus::from($request->status));

        return response()->json([
            'success' => true,
            'message' => 'Bill status updated successfully',
            'data' => $bill,
        ]);
    }

    /**
     * Calculate the total claimable amount for a user.
     */
    public function getClaimableAmount($userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $data = $this->billService->calculateAndEmailClaimableAmount($user);
        return response()->json($data);
    }

   public function getClaimableAmountForEmployees(): JsonResponse
{
    User::where('role', UserRole::EMPLOYEE->value)
        ->chunk(20, function ($users) {
            foreach ($users as $user) {
                $this->billService->calculateAndEmailClaimableAmount($user);
            }
        });

    return response()->json([
        'message' => 'Claimable amount processed for all employees.'
    ]);
}
}
