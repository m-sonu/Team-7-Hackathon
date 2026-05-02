<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBillRequest extends FormRequest
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
            'category_id' => 'nullable|integer|exists:category,id',
            'amount' => 'nullable|numeric',
            'bill_number' => 'nullable|string',
            'image_path' => 'nullable|string',
            'raw_text' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string',
            'items.*.price' => 'nullable|numeric',
            'items.*.is_claimable' => 'nullable|boolean',
            'vendor_contact' => 'nullable|array',
            'vendor_contact.company_name' => 'nullable|string',
            'vendor_contact.phone' => 'nullable|string',
            'vendor_contact.email' => 'nullable|email',
            'vendor_contact.website' => 'nullable|string',
        ];
    }
}
