<?php

namespace App\Jobs;

use App\Actions\NotifyUserOfBatchStatusAction;
use App\Actions\StoreBillAction;
use App\DTOs\AiParsedBillDTO;
use App\DTOs\StoreBillDTO;
use App\Enums\AiProcessStatus;
use App\Enums\BillStatus;
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
        $aiProcessStatus = AiProcessStatus::SUCCESS->value;
        try {
            DB::transaction(function () use ($storeBillAction, &$aiProcessStatus) {
                foreach ($this->storeBillDto->files as $file) {
                    $filePath = $file['path'];
                    $originalName = $file['original_name'];

                    try {
                        $fileContents = Storage::get($filePath);

                        $response = Http::connectTimeout(5)
                            ->timeout(300)
                            ->attach('file', $fileContents, $originalName)
                            ->post(config('services.tanuki.ai_url'));

                        $status = BillStatus::PENDING->value;
                        $reasonForAction = null;

                        if (! $response->json('success')) {
                            $errorDetail = $response->json('error_code')
                                ? $response->json('error_code').' - '.$response->json('message')
                                : 'Non-JSON or malformed response: '.$response->body();
                            Log::error("AI Parsing return null response for {$filePath}. ".$errorDetail);
                            $status = BillStatus::FAILED->value;
                            $reasonForAction = $response->json('message');
                        }

                        $aiData = $response->json('data');
                        logger()->info('This is data from ai : ', [$aiData]);

                        $aiData['bill']['status'] = $status;
                        $aiData['bill']['reason_for_action'] = $reasonForAction;


                        $aiDTO = AiParsedBillDTO::fromAiResponse($aiData);

                        $storeBillAction->execute(
                            $this->storeBillDto,
                            $filePath,
                            $aiDTO,
                            $originalName,
                            $this->batch
                        );
                    } catch (\Exception $e) {
                        $aiProcessStatus = AiProcessStatus::FAILED->value; // ← only place it flips
                        Log::error("Failed to process bill AI for file {$filePath}: ".$e->getMessage());
                    }
                }
            });
        } finally {
            $batchService = app(BillUploadBatchService::class);
            $batchService->validateAndSetBillStatuses($this->batch);

            $this->batch->update(['ai_processing' => $aiProcessStatus]);
            $notifyAction->execute($this->batch);
        }
    }
}
