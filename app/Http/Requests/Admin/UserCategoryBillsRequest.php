<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UserCategoryBillsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function month(): int
    {
        return (int) $this->input('month', now()->month);
    }

    public function year(): int
    {
        return (int) $this->input('year', now()->year);
    }

    public function startDate(): ?Carbon
    {
        $value = $this->input('start_date');
        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    public function endDate(): ?Carbon
    {
        $value = $this->input('end_date');
        return $value ? Carbon::parse($value)->endOfDay() : null;
    }
}
