<?php

namespace App\Jobs;

use App\Actions\NotifyUserOfBatchStatusAction;
use App\Actions\StoreBillAction;
use App\DTOs\AiParsedBillDTO;
use App\DTOs\StoreBillDTO;
use App\Models\BillUploadBatch;
use App\Services\BillUploadBatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessBillAiJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public StoreBillDTO $storeBillDto,
        public BillUploadBatch $batch
    ) {}

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(StoreBillAction $storeBillAction, NotifyUserOfBatchStatusAction $notifyAction): void
    {
        try {
            DB::transaction(function () use ($storeBillAction) {
                foreach ($this->storeBillDto->files as $file) {
                    $filePath = $file['path'];
                    $originalName = $file['original_name'];

                    try {
                        $fileContents = Storage::get($filePath);

                        $response = Http::timeout(300)->attach(
                            'file',
                            $fileContents,
                            $originalName
                        )->post(config('services.tanuki.ai_url'));

                        if ($response->failed()) {
                            Log::error("AI Parsing failed for {$filePath}: ".$response->body());

                            continue;
                        }

                        $aiData = $response->json('data');

                        logger()->info('This is data from ai : ', [$aiData]);
                        $aiDTO = AiParsedBillDTO::fromAiResponse($aiData);

                        $storeBillAction->execute(
                            $this->storeBillDto,
                            $filePath,
                            $aiDTO,
                            $originalName,
                            $this->batch
                        );
                    } catch (\Exception $e) {
                        Log::error("Failed to process bill AI for file {$filePath}: ".$e->getMessage());
                    }
                }
            });
        } finally {
            $batchService = app(BillUploadBatchService::class);
            $batchService->validateAndSetBillStatuses($this->batch);

            $this->batch->update(['ai_processing' => false]);
            $notifyAction->execute($this->batch);
        }
    }
}
