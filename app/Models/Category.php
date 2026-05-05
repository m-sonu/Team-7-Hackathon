<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public const TABLE_NAME = 'category';

    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'name',
        'monthly_limit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'monthly_limit' => 'decimal:2',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function monthlyPivots(): HasMany
    {
        return $this->hasMany(CategoryMonthlyPivot::class);
    }
}
