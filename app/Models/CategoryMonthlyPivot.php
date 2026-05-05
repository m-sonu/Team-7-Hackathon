<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMonthlyPivot extends Model
{
    use HasFactory;

    public const TABLE_NAME = 'category_monthly_pivot';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'user_id',
        'category_id',
        'month_year',
        'bill_count',
        'total_spent',
        'last_updated_at',
    ];

    protected $casts = [
        'total_spent' => 'decimal:2',
        'last_updated_at' => 'datetime',
        'bill_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
