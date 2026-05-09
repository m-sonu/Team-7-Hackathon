<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeUserBillsRequest extends FormRequest
{
      public function rules(): array
    {
        return [
            'month' => ['nullable', 'integer', 'between:1,12'],
            'status' => ['nullable', 'string'],
    
        ];
    }

    public function messages(): array
    {
        return [
            'month.between' => 'Month must be between 1 and 12.',
        ];
    }
}
