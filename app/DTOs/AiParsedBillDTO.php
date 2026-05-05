<?php

namespace App\DTOs;

class AiParsedBillDTO
{
    public function __construct(
        public readonly array $bill,
        public readonly array $billItems = [],
        public readonly array $vendorContact = []
    ) {}

    public static function fromAiResponse(array $data): self
    {
        return new self(
            bill: $data['bill'] ?? [],
            billItems: $data['bill_items'] ?? [],
            vendorContact: $data['vendor_contact'] ?? []
        );
    }
}
