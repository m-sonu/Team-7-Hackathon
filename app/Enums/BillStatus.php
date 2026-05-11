<?php

namespace App\Enums;

enum BillStatus: string
{
    case PENDING = 'pending'; //  status is set for valid bill until employee submits for reimbursement
    case UNDER_REVIEW = 'under review'; // status is set for valid bill after employee submits for reimbursement
    case INVALID = 'invalid'; // bill is invalid due to some reason
    case REJECTED = 'rejected'; // admin rejects bill
    case VERIFIED = 'verified'; // admin verifies bill
    case REIMBURSED = 'reimbursed'; // admin submits all the bills for reimbursement at the end of month

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::UNDER_REVIEW => 'Under Review',
            self::INVALID => 'Invalid',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::REIMBURSED => 'Paid',
        };
    }
}
