<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = [
        'user_id',
        'file_path',
        'original_name',
        'is_primary',
        'extracted_data',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'extracted_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
