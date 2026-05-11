<?php
namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number', 'client_id', 'payer_client_id', 'subscription_id',
        'invoice_date', 'due_date',
        'subtotal', 'tax_rate', 'tax_amount', 'total',
        'status', 'payment_method', 'payment_reference', 'paid_at',
        'notes', 'facturx_data',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'   => 'date',
            'due_date'       => 'date',
            'paid_at'        => 'date',
            'subtotal'       => 'decimal:2',
            'tax_rate'       => 'decimal:2',
            'tax_amount'     => 'decimal:2',
            'total'          => 'decimal:2',
            'status'         => InvoiceStatus::class,
            'payment_method' => PaymentMethod::class,
            'facturx_data'   => 'array',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'payer_client_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // ── Numérotation automatique ───────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $prefix = 'FA';
                $ym     = date('Ym');
                $last   = self::withTrashed()
                    ->where('invoice_number', 'like', "{$prefix}{$ym}%")
                    ->orderByDesc('invoice_number')
                    ->first();
                $seq = $last ? ((int) substr($last->invoice_number, 8)) + 1 : 1;
                $invoice->invoice_number = $prefix . $ym . str_pad($seq, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isSent(): bool
    {
        return $this->status === InvoiceStatus::Sent;
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Overdue;
    }

    public function isCancelled(): bool
    {
        return $this->status === InvoiceStatus::Cancelled;
    }

    /**
     * Recalcule subtotal, tax_amount et total depuis les lignes.
     */
    public function recalculateTotals(): void
    {
        $subtotal  = $this->lines->sum(fn($l) => $l->quantity * $l->unit_price);
        $taxAmount = round($subtotal * ($this->tax_rate ?? 2.10) / 100, 2);
        $total     = round($subtotal + $taxAmount, 2);

        $this->update([
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'total'      => $total,
        ]);
    }
}
