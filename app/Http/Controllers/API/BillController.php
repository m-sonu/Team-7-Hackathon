<?php

namespace App\Http\Controllers\API;

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
    public function submitBatch(BillUploadBatch $batch, SubmitBillForReimburseAction $action): JsonResponse
    {
        $this->authorize('view', $batch);

        $count = $action->execute($batch);

        return response()->json([
            'message' => "Successfully submitted {$count} bills for reimbursement.",
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

    /**
     * View the file associated with the bill.
     */
    public function viewFile(Request $request, Bill $bill): mixed
    {
        $user = $request->user();

        // Check if user is owner or admin
        if ($user->id !== $bill->user_id && $user->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $media = $bill->getFirstMedia('bills');

        if (! $media) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file($media->getPath());
    }
}
