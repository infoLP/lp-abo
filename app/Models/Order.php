<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'number', 'client_id', 'beneficiary_id', 'beneficiary_ids',
        'billing_name', 'billing_address1', 'billing_address2', 'billing_address3',
        'billing_postal_code', 'billing_city', 'billing_cedex', 'billing_country',
        'delivery_company', 'delivery_recipient',
        'delivery_address1', 'delivery_address2', 'delivery_address3',
        'delivery_postal_code', 'delivery_city', 'delivery_cedex', 'delivery_country',
        'status', 'order_date', 'validated_at', 'installed_at',
        'subtotal', 'discount_amount', 'discount_percent',
        'total_ht', 'total_tva', 'total_ttc',
        'invoice_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'           => OrderStatus::class,
            'order_date'       => 'date',
            'validated_at'     => 'date',
            'installed_at'     => 'date',
            'subtotal'         => 'decimal:2',
            'discount_amount'  => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'total_ht'         => 'decimal:2',
            'total_tva'        => 'decimal:2',
            'total_ttc'        => 'decimal:2',
            'beneficiary_ids'  => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->number)) {
                $order->number = static::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        $prefix = 'CMD' . now()->format('Ym');
        $last   = static::withTrashed()
                        ->where('number', 'like', $prefix . '%')
                        ->orderByDesc('number')->first();
        $seq    = $last ? ((int) substr($last->number, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'beneficiary_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class)->orderBy('sort_order');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Retourne la liste des bénéficiaires (multi ou simple)
     * @return \Illuminate\Support\Collection<Client>
     */
    public function getBeneficiariesCollection(): \Illuminate\Support\Collection
    {
        // Cas multi-bénéficiaires
        if (! empty($this->beneficiary_ids)) {
            return Client::whereIn('id', $this->beneficiary_ids)->get();
        }
        // Cas bénéficiaire unique
        if ($this->beneficiary_id) {
            return Client::where('id', $this->beneficiary_id)->get();
        }
        // Pas de bénéficiaire distinct → le payeur est son propre bénéficiaire
        return collect();
    }

    public function isDraft(): bool
    {
        return $this->status === OrderStatus::Brouillon;
    }

    public function isValidee(): bool
    {
        return $this->status === OrderStatus::Validee;
    }

    public function isInstallee(): bool
    {
        return $this->status === OrderStatus::Installee;
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeValideeNonPayee($query)
    {
        return $query->where('status', OrderStatus::Validee)
                     ->whereNull('invoice_id');
    }

    public function recalculateTotals(): void
    {
        $subtotal  = $this->lines->sum('total_ttc');
        $remise    = $this->discount_percent > 0
                     ? round($subtotal * $this->discount_percent / 100, 2)
                     : $this->discount_amount;

        $totalTtc  = max(0, $subtotal - $remise);
        $totalTva  = $this->lines->sum('total_tva');
        $totalHt   = $totalTtc - $totalTva;

        $this->update([
            'subtotal'        => $subtotal,
            'discount_amount' => $remise,
            'total_ttc'       => $totalTtc,
            'total_tva'       => $totalTva,
            'total_ht'        => $totalHt,
        ]);
    }
}
