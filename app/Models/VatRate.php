<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VatRate extends Model
{
    public const ZONES = [
        'metropole'        => 'Métropole',
        'corse'            => 'Corse',
        'dom'              => 'DOM',
        'ue_sans_intracom' => 'UE sans intracom',
        'ue_avec_intracom' => 'UE avec intracom',
        'international'    => 'International',
    ];

    protected $fillable = [
        'name', 'slug', 'usage',
        'metropole_accounting_code',        'metropole_rate',
        'corse_accounting_code',            'corse_rate',
        'dom_accounting_code',              'dom_rate',
        'ue_sans_intracom_accounting_code', 'ue_sans_intracom_rate',
        'ue_avec_intracom_accounting_code', 'ue_avec_intracom_rate',
        'international_accounting_code',    'international_rate',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'metropole_rate'        => 'float',
        'corse_rate'            => 'float',
        'dom_rate'              => 'float',
        'ue_sans_intracom_rate' => 'float',
        'ue_avec_intracom_rate' => 'float',
        'international_rate'    => 'float',
    ];

    protected static function booted(): void
    {
        // Slug auto
        static::saving(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name, '_');
            }
            // Supprimer les espaces dans les codes comptables
            foreach (array_keys(self::ZONES) as $zone) {
                $col = "{$zone}_accounting_code";
                if (!empty($model->{$col})) {
                    $model->{$col} = str_replace(' ', '', $model->{$col});
                }
            }
        });
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AccountingAssignment::class);
    }

    public function rateForZone(string $zone): ?float
    {
        return $this->{"{$zone}_rate"};
    }

    public function codeForZone(string $zone): ?string
    {
        return $this->{"{$zone}_accounting_code"};
    }
}
