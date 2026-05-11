<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuxiliaryCode extends Model
{
    protected $fillable = [
        'code', 'label', 'description', 'magazine_id', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->code = str_replace(' ', '', $model->code);
        });
    }

    public function magazine(): BelongsTo
    {
        return $this->belongsTo(Magazine::class);
    }
}
