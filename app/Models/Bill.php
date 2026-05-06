<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Bill extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('bills')
            ->useDisk('local')
            ->singleFile();
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PAID = 'paid';

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


    public function vendorContact(): HasOne
    {
        return $this->hasOne(VendorContact::class);
    }
}
