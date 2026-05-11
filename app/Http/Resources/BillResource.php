<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
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
            'bill_no' => $this->bill_no,
            'vat_no' => $this->vat_no,
            'amount' => format_currency($this->amount ?? 0, $this->billUploadBatch->currency),
            'approved_amount' => format_currency($this->approve_amount ?? 0, $this->billUploadBatch->currency),
            'status' => $this->status,
            'is_valid' => $this->is_valid ?? true,
            'validation_error' => $this->validation_error,
            'file_preview_url' => $this->file_preview_url,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'billUploadBatch' => $this->whenLoaded('billUploadBatch', function () {
                return [
                    'id' => $this->billUploadBatch->id,
                    'title' => $this->billUploadBatch->title,
                    'currency' => $this->billUploadBatch->currency,
                    'category' => $this->billUploadBatch->category?->name,
                ];
            }),
            'vendorContact' => $this->whenLoaded('vendorContact', function () {
                return [
                    'id' => $this->vendorContact->id,
                    'company_name' => $this->vendorContact->company_name,
                    'phone' => $this->vendorContact->phone,
                ];
            }),
            'created_at' => $this->created_at->format('M d, Y'),
            'updated_at' => $this->updated_at->format('M d, Y'),
        ];
    }
}
