<?php

namespace App\Http\Resources;

use App\Enums\BillStatus;
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
            'category' => $this->category ? [
                'en' => $this->category->name,
                'jp' => $this->category->jp_name,
            ] : null,
            'created_date' => $this->created_at->format('M d Y'),
            'approved_amount' => \format_currency($this->bills_sum_approve_amount ?? 0, $this->currency),
            'amount' => \format_currency($this->bills_sum_amount ?? 0, $this->currency),
            'ai_processing' => $this->ai_processing,
            'status' => $this->determineBatchStatus(),
            'bills_count' => $this->bills_count,
            'bills' => BillResource::collection($this->whenLoaded('bills')),
        ];
    }

    /**
     * Determine a representative status for the batch.
     * Active statuses (pending, under_review, verified) take priority over settled ones.
     */
    protected function determineBatchStatus(): string
    {
        if ($this->bills_count_pending > 0) {
            return BillStatus::PENDING->value;
        }

        if ($this->bills_count_under_review > 0) {
            return BillStatus::UNDER_REVIEW->value;
        }

        if ($this->bills_count_verified > 0) {
            return BillStatus::VERIFIED->value;
        }

        if ($this->bills_count_paid > 0) {
            return BillStatus::REIMBURSED->value;
        }

        if ($this->bills_count_rejected > 0) {
            return BillStatus::REJECTED->value;
        }

        return BillStatus::PENDING->value;
    }
}
