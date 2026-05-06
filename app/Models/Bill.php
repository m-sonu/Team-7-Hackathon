<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bill extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'bill';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'category_id',
        'category_monthly_pivot_id',
        'bill_no',
        'vat_no',
        'amount',
        'approve_amount',
        'status',
        'file_path',
        'raw_text',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function vendorContact(): HasOne
    {
        return $this->hasOne(VendorContact::class);
    }
}
