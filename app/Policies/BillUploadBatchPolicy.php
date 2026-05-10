<?php

namespace App\Policies;

use App\Models\BillUploadBatch;
use App\Models\User;

class BillUploadBatchPolicy
{
    /**
     * Determine whether the user can view the batch preview.
     */
    public function view(User $user, BillUploadBatch $batch): bool
    {
        return $user->id === $batch->user_id || $user->role === 'admin';
    }
}
