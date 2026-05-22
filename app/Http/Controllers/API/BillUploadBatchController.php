<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Resources\BillUploadBatchPreviewResource;
use App\Models\BillUploadBatch;
use App\Services\BillUploadBatchService;
use Illuminate\Http\JsonResponse;

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
}
