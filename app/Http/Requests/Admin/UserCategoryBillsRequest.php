<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UserCategoryBillsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
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
}
