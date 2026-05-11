<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportMapping extends Model
{
    protected $fillable = [
        'name', 'description', 'mapping', 'options', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'mapping' => 'array',
            'options' => 'array',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
