<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingCode extends Model
{
    protected $fillable = [
        'code', 'label', 'description', 'type', 'is_active', 'sort_order',
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

    public static function types(): array
    {
        return [
            'vente'     => 'Vente',
            'tva'       => 'TVA',
            'livraison' => 'Livraison',
            'autre'     => 'Autre',
        ];
    }

    public function getDisplayAttribute(): string
    {
        return "{$this->code} — {$this->label}";
    }
}
