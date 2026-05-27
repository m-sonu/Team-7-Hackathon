<?php

namespace App\Http\Resources;

use App\Enums\BillStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class BillResource extends JsonResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $updatedCategoryLimit = $request->attributes->get('updated_category_limit');
        $billAmount = (float) ($this->amount ?? 0);

        $claimableAmount = $this->getClaimableAmount($updatedCategoryLimit, $billAmount);

        $billUploadBatch = $this->billUploadBatch;
        $batchCurrency = $billUploadBatch->currency;

        return [
            'id' => $this->id,
            'category_monthly_pivot_id' => $this->category_monthly_pivot_id,
            'bill_no' => $this->bill_no,
            'vat_no' => $this->vat_no,
            'amount' => format_currency($this->amount ?? 0, $batchCurrency),
            'amount_raw' => (float) ($this->amount ?? 0),
            'approved_amount' => format_currency($this->approve_amount ?? 0, $batchCurrency),
            'claimable_amount' => $claimableAmount ? format_currency($claimableAmount, $batchCurrency) : null,
            'status' => $this->status,
            'is_valid' => $this->is_valid ?? true,
            'validation_error' => $this->reason_for_action,
            'reason_for_action' => $this->reason_for_action,
            'file_preview_url' => URL::signedRoute('bills.file', [
                'bill' => $this->id,
                'user' => auth('sanctum')->id(),
            ]),
            'billUploadBatch' => $this->whenLoaded('billUploadBatch', function () use ($billUploadBatch) {
                return [
                    'id' => $billUploadBatch->id,
                    'title' => $billUploadBatch->title,
                    'currency' => $billUploadBatch->currency,
                    'category' => $billUploadBatch->category?->name,
                ];
            }),
            'vendorContact' => $this->whenLoaded('vendorContact', function () {
                $vendorContact = $this->vendorContact;

                return [
                    'id' => $vendorContact->id,
                    'company_name' => $vendorContact->company_name,
                    'phone' => $vendorContact->phone,
                ];
            }),
            'created_at' => $this->created_at->format('M d, Y'),
            'updated_at' => $this->updated_at->format('M d, Y'),
        ];
    }

    public function getClaimableAmount(mixed $updatedCategoryLimit, float $billAmount): ?float
    {
        if (in_array($this->status->value, [BillStatus::VERIFIED->value, BillStatus::REIMBURSED->value])) {
            return null;
        }

        return $updatedCategoryLimit === null
            ? null
            : min($updatedCategoryLimit, $billAmount);
    }
}
