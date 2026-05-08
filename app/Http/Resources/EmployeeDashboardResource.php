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
            'total_bills' => (int) $this['stats']->total_bills,
            'total_approved_amount' => format_currency($this['stats']->total_approved_amount ?? 0, $this['currency']),
            'current_month_total_approved_amount' => format_currency($this['stats']->total_approved_amount ?? 0, $this['currency']),
            'current_month_verified_bills' => (int) $this['stats']->verified_bills_count,
            'category_wise_amounts' => $this['category_wise_amounts']->map(function ($item) {
                return [
                    'category_id' => $item->category_id,
                    'category' => $item->category,
                    'approved_amount' => format_currency($item->approved_amount,$this['currency']),
                    'bill_count' => (int) $item->bill_count,
                ];
            }),
        ];
    }

    /**
     * Customize the outgoing response signals.
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
        ];
    }
}