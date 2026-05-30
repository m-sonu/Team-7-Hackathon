<?php
namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'category_id' => $this->category_id,
            'category' => [
                'en' => $this->category_name,
                'jp' => $this->category_jp_name,
            ],
            'total_amount' => format_currency($this->total_amount, $this->currency ?? ''),
            'approved_amount' => format_currency($this->approved_amount, $this->currency ?? ''),
            'bill_count' => $this->bill_count,
            'status' => $this->highest_status,
            'updated_at' => Carbon::parse($this->updated_at)->format('Y-m-d'),
        ];
    }
}
