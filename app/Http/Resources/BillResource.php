<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

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
            'category_monthly_pivot_id' => $this->category_monthly_pivot_id,
            'bill_no' => $this->bill_no,
            'vat_no' => $this->vat_no,
            'amount' => format_currency($this->amount ?? 0, $this->billUploadBatch->currency),
            'amount_raw' => (float) ($this->amount ?? 0),
            'approved_amount' => format_currency($this->approve_amount ?? 0, $this->billUploadBatch->currency),
            'status' => $this->status,
            'is_valid' => $this->is_valid ?? true,
            'validation_error' => $this->reason_for_action,
            'file_preview_url' => URL::signedRoute('bills.file', [
                'bill' => $this->id,
                'user' => auth('sanctum')->id(),
            ]),
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
