<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // We assume the data is passed as an object or associative array
        
        return [
            'category_id' => $this->category_id,
            'category' => [
                'en' => $this->category_en,
                'jp' => $this->category_jp ?? '',
            ],
            'approved_amount' => format_currency($this->approved_amount, $this->currency),
            'amount' => format_currency($this->total_amount, $this->currency),
            'bill_count' => (int) $this->bill_count,
        ];
    }
    /**
     * Customize the outgoing response signals.d
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}
