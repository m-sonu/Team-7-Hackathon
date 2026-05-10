<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'amount' => \format_currency($this->total_amount ?? 0, $this->currency),
            'approved_amount' => \format_currency($this->total_approve_amount ?? 0, $this->currency),
            'status' => $this->status,
        ];
    }
}
