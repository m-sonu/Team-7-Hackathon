<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'total_amount' => format_currency($this->total_amount, $this->currency ?? ''),
            'approved_amount' => format_currency($this->approved_amount, $this->currency ?? ''),
            'bill_count' => $this->bill_count,
            'status' => $this->highest_status,
        ];
    }
}