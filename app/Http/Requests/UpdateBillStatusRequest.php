<?php

namespace App\Http\Requests;

use App\Models\Bill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBillStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric',
            'bill_no' => 'required|string',
            'vat_no' => 'required|string',
            'status' => 'required|string|in:'.implode(',', [
                Bill::STATUS_PENDING,
                Bill::STATUS_VERIFIED,
                Bill::STATUS_REJECTED,
                Bill::STATUS_PAID,
            ]),
        ];
    }
}
