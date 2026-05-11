<?php

namespace App\Models;

use App\Enums\BillStatus;
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

    public const TABLE_NAME = 'bill';

    protected $table = self::TABLE_NAME;

    protected $casts = [
        'status' => BillStatus::class,
    ];

    protected $fillable = [
        'user_id',
        'category_id',
        'category_monthly_pivot_id',
        'bill_upload_batch_id',
        'bill_no',
        'vat_no',
        'amount',
        'approve_amount',
        'status',
        'validation_error',
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

    public function billUploadBatch(): BelongsTo
    {
        return $this->belongsTo(BillUploadBatch::class);
    }

    public function vendorContact(): HasOne
    {
        return $this->hasOne(VendorContact::class);
    }
}
