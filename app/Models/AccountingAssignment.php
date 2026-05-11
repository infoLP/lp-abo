<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingAssignment extends Model
{
    protected $fillable = [
        'label', 'type', 'magazine_id', 'vat_rate_id',
        'metropole_accounting_code',
        'corse_accounting_code',
        'dom_accounting_code',
        'ue_sans_intracom_accounting_code',
        'ue_avec_intracom_accounting_code',
        'international_accounting_code',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $zones = ['metropole','corse','dom','ue_sans_intracom','ue_avec_intracom','international'];
            foreach ($zones as $zone) {
                $col = "{$zone}_accounting_code";
                if (!empty($model->{$col})) {
                    $model->{$col} = str_replace(' ', '', $model->{$col});
                }
            }
        });
    }

    public static function types(): array
    {
        return [
            'abonnement' => 'Vente Abonnement',
            'revue'      => 'Vente Revue',
            'livraison'  => 'Livraison',
        ];
    }

    public function magazine(): BelongsTo
    {
        return $this->belongsTo(Magazine::class);
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    public function codeForZone(string $zone): ?string
    {
        return $this->{"{$zone}_accounting_code"};
    }
}
