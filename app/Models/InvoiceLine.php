<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id', 'description', 'quantity', 'unit_price', 'total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total'      => 'decimal:2',
        ];
    }

    // ── Boot ───────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        /**
         * Calcul automatique du total avant chaque sauvegarde.
         * total = quantity * unit_price
         */
        static::saving(function (self $line) {
            $qty   = (float) ($line->quantity   ?? 0);
            $price = (float) ($line->unit_price ?? 0);
            $line->total = round($qty * $price, 2);
        });
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
