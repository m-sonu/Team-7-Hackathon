<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Mail\AdminReimbursementNotificationMailable;
use App\Models\Bill;
use App\Models\BillUploadBatch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendReimbursementNotificationToAdmin
{
    /**
     * Send a reimbursement submission notification to all admin users.
     *
     * @param  Collection<int, Bill>  $bills
     */
    public function execute(User $requester, Collection $bills, int $batchId): void
    {
        $admins = User::query()
            ->where('role', UserRole::ADMIN)
            ->get();

        $batch = BillUploadBatch::query()->find($batchId);

        if ($admins->isEmpty()) {
            Log::warning('No admin users found. Skipping reimbursement notification.', [
                'requester_id' => $requester->id,
                'batch_id' => $batch->id,
            ]);

            return;
        }

        try {
            Mail::to($admins)->queue(
                new AdminReimbursementNotificationMailable(
                    requester: $requester,
                    batch: $batch,
                    bills: $bills,
                )
            );
        } catch (Throwable $e) {
            Log::error('Failed to queue reimbursement notification email to admins.', [
                'requester_id' => $requester->id,
                'batch_id' => $batch->id,
                'admin_ids' => $admins->pluck('id')->all(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
