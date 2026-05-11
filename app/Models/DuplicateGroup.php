<?php

namespace App\Models;

use App\Enums\DuplicateStatus;
use App\Enums\MatchType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DuplicateGroup extends Model
{
    protected $fillable = [
        'match_type', 'match_value', 'confidence_score', 'status',
        'clients_count', 'detected_at', 'resolved_at',
        'resolved_by_id', 'resolution_notes',
    ];

    protected $casts = [
        'match_type'       => MatchType::class,
        'status'           => DuplicateStatus::class,
        'detected_at'      => 'datetime',
        'resolved_at'      => 'datetime',
        'confidence_score' => 'integer',
        'clients_count'    => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(DuplicateGroupItem::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    // ── Accesseurs — délèguent aux Enums ───────────────────────────────────────

    public function getMatchTypeLabelAttribute(): string
    {
        return $this->match_type instanceof MatchType
            ? $this->match_type->label()
            : (string) $this->match_type;
    }

    public function getMatchTypeIconAttribute(): string
    {
        return $this->match_type instanceof MatchType
            ? $this->match_type->icon()
            : 'heroicon-o-document-duplicate';
    }

    public function getMatchTypeColorAttribute(): string
    {
        return $this->match_type instanceof MatchType
            ? $this->match_type->color()
            : 'gray';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status instanceof DuplicateStatus
            ? $this->status->label()
            : (string) $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status instanceof DuplicateStatus
            ? $this->status->color()
            : 'gray';
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', DuplicateStatus::Pending);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', [DuplicateStatus::Merged, DuplicateStatus::Dismissed]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

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
            'status'           => DuplicateStatus::Dismissed,
            'resolved_at'      => now(),
            'resolved_by_id'   => $userId,
            'resolution_notes' => $notes,
        ]);
    }

    public function markMerged(?int $userId = null, ?string $notes = null): void
    {
        $this->update([
            'status'           => DuplicateStatus::Merged,
            'resolved_at'      => now(),
            'resolved_by_id'   => $userId,
            'resolution_notes' => $notes,
        ]);
    }
}
