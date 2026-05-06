<?php

namespace App\Models;

use Database\Factories\BillUploadBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillUploadBatch extends Model
{
    /** @use HasFactory<BillUploadBatchFactory> */
    use HasFactory;

    public const TABLE_NAME = 'bill_upload_batch';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'title',
        'currency',
        'user_id',
        'category_id',
        'category_monthly_pivot_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function categoryMonthlyPivot(): BelongsTo
    {
        return $this->belongsTo(CategoryMonthlyPivot::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }
}
