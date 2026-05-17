<?php

namespace App\Http\Requests\API;

use App\Enums\BillStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class VerifyBillRequest extends FormRequest
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
            'status' => ['required', new Enum(BillStatus::class)],
            'approve_amount' => [
                'required_if:status,'.BillStatus::VERIFIED->value,
                'numeric',
                'min:0',
            ],
            'reason_for_action' => ['nullable', 'string', 'max:500'],
        ];
    }
}
