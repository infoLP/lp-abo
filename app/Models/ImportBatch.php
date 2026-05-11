<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    protected $fillable = [
        'filename', 'type', 'status',
        'total_rows', 'processed_rows', 'success_rows', 'error_rows',
        'errors', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
