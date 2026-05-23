<?php

namespace App\Http\Controllers\API;

use App\Actions\SendReimbursementNotificationToAdmin;
use App\Actions\SubmitBillForReimburseAction;
use App\Http\Controllers\ApiController;
use App\Http\Resources\BillUploadBatchPreviewResource;
use App\Models\BillUploadBatch;
use App\Services\BillUploadBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BillUploadBatchController extends ApiController
{
    public function __construct(
        protected BillUploadBatchService $service
    ) {}

    /**
     * Preview a bill upload batch.
     */
    public function preview(BillUploadBatch $batch): JsonResponse
    {
        $this->authorize('view', $batch);

        $previewData = $this->service->getBatchPreview($batch->id);

        return $this->sendResponse(
            (new BillUploadBatchPreviewResource($previewData))->resolve(),
            'success'
        );
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
            return $this->sendError(
                "There are no bills to send reimbursement notification for {$batch->id}",
                Response::HTTP_BAD_REQUEST
            );
        }

        $notifyAction->execute(
            requester: $batch->user,
            bills: $bills,
            batchId: $batch->id
        );

        return $this->sendResponse([], "Successfully submitted {$bills->count()} bills for reimbursement.");
    }
}
