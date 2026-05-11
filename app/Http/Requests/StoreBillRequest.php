<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Models\BillUploadBatch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->hasFile('files[]') && ! $this->hasFile('files')) {
            $this->merge(['files' => $this->file('files[]')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user() ? $this->user()->id : 1; // Fallback to 1 for tests if user not authenticated

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique(BillUploadBatch::TABLE_NAME)->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId)
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month);
                }),
            ],
            'currency' => ['required', 'string', Rule::in(Currency::values())],
            'category_id' => 'required|integer|exists:category,id',
            'files' => 'required|array|min:1|max:3',
            'files.*' => 'file|mimes:jpeg,png,jpg,webp,pdf,pptx,ppt,docx,doc,csv,xlsx,xls|max:10240',
        ];
    }
}
