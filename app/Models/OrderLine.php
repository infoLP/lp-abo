<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLine extends Model
{
    protected $fillable = [
        'order_id', 'magazine_id', 'subscription_plan_id',
        'start_date', 'end_date', 'issues_count',
        'unit_price', 'tva_rate',
        'total_ht', 'total_tva', 'total_ttc',
        'support', 'subscription_id', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'unit_price' => 'decimal:2',
            'tva_rate'   => 'decimal:2',
            'total_ht'   => 'decimal:2',
            'total_tva'  => 'decimal:2',
            'total_ttc'  => 'decimal:2',
        ];
    }

    /**
     * Calcul automatique des montants avant chaque sauvegarde
     */
    protected static function booted(): void
    {
        static::saving(function (self $line) {
            $price = (float) ($line->unit_price ?? 0);
            $rate  = (float) ($line->tva_rate ?? 2.10);

            if ($price > 0) {
                $tva = round($price * $rate / (100 + $rate), 2);
                $ht  = round($price - $tva, 2);

                $line->total_ttc = $price;
                $line->total_tva = $tva;
                $line->total_ht  = $ht;
                $line->tva_rate  = $rate;
            } else {
                $line->total_ttc = 0;
                $line->total_tva = 0;
                $line->total_ht  = 0;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function magazine(): BelongsTo
    {
        return $this->belongsTo(Magazine::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
