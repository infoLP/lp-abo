<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_number', 'client_id', 'invoice_id', 'subscription_id',
        'amount', 'method', 'status', 'reference',
        'stripe_payment_id', 'payment_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    // ── Boot ───────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Payment $p) {
            if (empty($p->payment_number)) {
                $prefix = 'PAY';
                $ym     = date('Ym');
                $last   = static::withTrashed()
                    ->where('payment_number', 'like', "{$prefix}{$ym}%")
                    ->orderByDesc('payment_number')
                    ->first();
                $seq = $last ? ((int) substr($last->payment_number, 9)) + 1 : 1;
                $p->payment_number = $prefix . $ym . str_pad($seq, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
