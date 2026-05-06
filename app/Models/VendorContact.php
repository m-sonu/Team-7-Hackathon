<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorContact extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'vendor_contact';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'bill_id',
        'company_name',
        'phone',
        'email',
        'website',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
