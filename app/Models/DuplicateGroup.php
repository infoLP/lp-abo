<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateGroup extends Model
{
    protected $fillable = [
        'match_type',
        'match_value',
        'confidence_score',
        'status',
        'clients_count',
        'detected_at',
        'resolved_at',
        'resolved_by_id',
        'resolution_notes',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
        'confidence_score' => 'integer',
        'clients_count' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(DuplicateGroupItem::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function getMatchTypeLabelAttribute(): string
    {
        return match ($this->match_type) {
            'email'        => 'Email identique',
            'siret'        => 'SIRET identique',
            'name_postal'  => 'Nom + Prenom + CP',
            'phone'        => 'Telephone identique',
            'company_city' => 'Raison sociale + Ville',
            default        => $this->match_type,
        };
    }

    public function getMatchTypeIconAttribute(): string
    {
        return match ($this->match_type) {
            'email'        => 'heroicon-o-envelope',
            'siret'        => 'heroicon-o-building-office',
            'name_postal'  => 'heroicon-o-user',
            'phone'        => 'heroicon-o-phone',
            'company_city' => 'heroicon-o-building-storefront',
            default        => 'heroicon-o-document-duplicate',
        };
    }

    public function getMatchTypeColorAttribute(): string
    {
        return match ($this->match_type) {
            'email'        => 'danger',
            'siret'        => 'danger',
            'name_postal'  => 'warning',
            'phone'        => 'info',
            'company_city' => 'gray',
            default        => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'En attente',
            'merged'    => 'Fusionne',
            'dismissed' => 'Ignore',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'merged'    => 'success',
            'dismissed' => 'gray',
            default     => 'gray',
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['merged', 'dismissed']);
    }

    public function getMasterItem(): ?DuplicateGroupItem
    {
        return $this->items()->where('is_master', true)->first();
    }

    public function setMaster(int $clientId): void
    {
        $this->items()->update(['is_master' => false]);
        $this->items()->where('client_id', $clientId)->update(['is_master' => true]);
    }

    public function dismiss(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'resolved_at' => now(),
            'resolved_by_id' => $userId,
            'resolution_notes' => $notes,
        ]);
    }

    public function markMerged(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'merged',
            'resolved_at' => now(),
            'resolved_by_id' => $userId,
            'resolution_notes' => $notes,
        ]);
    }
}
