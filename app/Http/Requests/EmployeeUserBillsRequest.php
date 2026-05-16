<?php

namespace App\Http\Requests;

use App\Enums\BillStatus;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeUserBillsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'integer', 'between:1,12'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(BillStatus::cases(), 'value'))],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.between' => 'Month must be between 1 and 12.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
