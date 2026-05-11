<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticalSection extends Model
{
    protected $fillable = [
        'code', 'label', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
