<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillUploadBatchDetailResource extends JsonResource
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
            'approved_amount' => format_currency($this->bills_sum_approve_amount ?? 0, $this->currency),
            'bills' => BillResource::collection($this->bills), // Always include bills here
        ];
    }
}
