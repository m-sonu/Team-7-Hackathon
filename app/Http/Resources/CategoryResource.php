<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'jp_name' => $this->jp_name,
            'locale' => [
                'en' => $this->name,
                'jp' => $this->jp_name ?? '',
            ],
            'monthly_limit' => $this->monthly_limit,
            'is_active' => $this->is_active,
        ];
    }
}
