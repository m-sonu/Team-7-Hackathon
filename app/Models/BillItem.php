<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $table = 'bill_item';

    protected $fillable = [
        'bill_id',
        'name',
        'price',
        'is_claimable',
        'rejection_reason',
    ];

    protected $casts = [
        'is_claimable' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
