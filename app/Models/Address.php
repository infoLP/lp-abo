<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'name', 'address_type', 'usage', 'is_default',
        'l1', 'l2', 'l3', 'l4', 'l5',
        'l6_postal_code', 'l6_city', 'l6_cedex', 'l6_state_code', 'l7_country',
        'rnvp_valid', 'rnvp_checked_at', 'rnvp_status',
    ];

    protected $casts = [
        'is_default'       => 'boolean',
        'rnvp_valid'       => 'boolean',
        'rnvp_checked_at'  => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Adresse formatée sur plusieurs lignes
     */
    public function formatted(): string
    {
        $lines = array_filter([
            $this->l1,
            $this->l2,
            $this->l3,
            $this->l4,
            $this->l5,
            trim(implode(' ', array_filter([
                $this->l6_postal_code,
                $this->l6_city,
                $this->l6_cedex ? 'CEDEX ' . $this->l6_cedex : null,
            ]))),
            $this->l7_country !== 'FR' ? $this->l7_country : null,
        ]);

        return implode("\n", $lines);
    }

    /**
     * Lignes RNVP sous forme de tableau (max 38 car chacune)
     */
    public function rnvpLines(): array
    {
        return array_values(array_filter([
            $this->l1,
            $this->l2,
            $this->l3,
            $this->l4,
            $this->l5,
            trim(implode(' ', array_filter([
                $this->l6_postal_code,
                $this->l6_city,
                $this->l6_cedex ? 'CEDEX ' . $this->l6_cedex : null,
            ]))),
            $this->l7_country !== 'FR' ? $this->l7_country : null,
        ]));
    }
}
