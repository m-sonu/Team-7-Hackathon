<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currency = $this->bills->first()?->billUploadBatch?->currency;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'bills_count' => (int) ($this->bills_count ?? 0),
            'total_amount' => format_currency($this->total_amount ?? 0, $currency),
            'approved_amount' => format_currency($this->total_approve_amount ?? 0, $currency),
        ];
    }
}
