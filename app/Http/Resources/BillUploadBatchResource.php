<?php

namespace App\Http\Resources;

use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillUploadBatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category?->name,
            'created_date' => $this->created_at->format('M d y'),
            'approved_amount' => (float) ($this->bills_sum_approve_amount ?? 0),
            'status' => $this->determineBatchStatus(),
            'bills' => BillResource::collection($this->whenLoaded('bills')),
        ];
    }

    /**
     * Determine a representative status for the batch.
     */
    protected function determineBatchStatus(): string
    {
        if ($this->bills_count_pending > 0) {
            return Bill::STATUS_PENDING;
        }

        if ($this->bills_count_verified > 0) {
            return Bill::STATUS_VERIFIED;
        }

        if ($this->bills_count_paid > 0) {
            return Bill::STATUS_PAID;
        }

        if ($this->bills_count_rejected > 0) {
            return Bill::STATUS_REJECTED;
        }

        return Bill::STATUS_PENDING; // Default
    }
}
