<?php

namespace App\Models;

use App\Enums\SubscriptionMode;
use App\Enums\SupportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'magazine_id', 'name', 'slug', 'description', 'support_type',
        'mode', 'duration_months', 'issues_count', 'price',
        'is_free', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'support_type' => SupportType::class,
            'mode'         => SubscriptionMode::class,
            'price'        => 'decimal:2',
            'is_free'      => 'boolean',
            'is_active'    => 'boolean',
        ];
    }

    // ── Boot ───────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (self $plan) {
            if (empty($plan->slug) || $plan->isDirty('name')) {
                $base = Str::slug($plan->name);
                $slug = $base;
                $i    = 1;
                while (static::where('slug', $slug)->where('id', '!=', $plan->id ?? 0)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $plan->slug = $slug;
            }
        });
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function magazine(): BelongsTo
    {
        return $this->belongsTo(Magazine::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ── Accesseurs ─────────────────────────────────────────────────────────────

    public function getFormattedPriceAttribute(): string
    {
        return $this->is_free
            ? 'Gratuit'
            : number_format($this->price, 2, ',', ' ') . ' EUR';
    }
}
