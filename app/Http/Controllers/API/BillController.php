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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
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
    public function getClaimableAmount(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->has('user_id') && $user->role === UserRole::ADMIN) {
            $user = User::findOrFail($request->user_id);
        }

        $data = $this->billService->calculateAndEmailClaimableAmount($user);

        return response()->json($data);
    }
}
