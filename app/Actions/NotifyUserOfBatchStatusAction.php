<?php

namespace App\Actions;

use App\Mail\BatchStatusMail;
use App\Models\BillUploadBatch;
use App\Services\BillUploadBatchService;
use Illuminate\Support\Facades\Mail;

class NotifyUserOfBatchStatusAction
{
    /**
     * Process the batch validation and notify the user.
     */
    public function execute(BillUploadBatch $batch): void
    {
        $service = app(BillUploadBatchService::class);
        $previewData = $service->getBatchPreview($batch->id);

        // Send consolidated email
        Mail::to($batch->user->email)->send(
            new BatchStatusMail(
                $previewData['batch'],
                $previewData['valid_bills'],
                $previewData['invalid_bills'],
                config('app.frontend_url')."/review-batch/{$batch->id}"
            )
        );
    }
}
