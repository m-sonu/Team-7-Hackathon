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
             'amount' => format_currency($this->amount ?? 0),
            'approved_amount' => format_currency($this->approve_amount ?? 0),
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'batch' => $this->whenLoaded('batch', function () {
                return [
                    'id' => $this->batch->id,
                    'title' => $this->batch->title,
                    'currency' => $this->batch->currency,
                    'category' => $this->batch->category?->name,
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
