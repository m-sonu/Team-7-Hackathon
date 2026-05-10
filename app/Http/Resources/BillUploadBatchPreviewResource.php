<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillUploadBatchPreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['batch']->id,
            'title' => $this['batch']->title,
            'currency' => $this['batch']->currency,
            'category' => $this['batch']->category?->name,
            'submitted_at' => $this['batch']->created_at->format('Y-m-d H:i:s'),
            'ai_processing' => $this['batch']->ai_processing,
            'totals' => $this['totals'],
            'valid_bills' => BillResource::collection($this['valid_bills']),
            'invalid_bills' => BillResource::collection($this['invalid_bills']),
        ];
    }
}
