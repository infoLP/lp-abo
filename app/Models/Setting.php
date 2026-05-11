<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'group', 'key', 'label', 'description',
        'type', 'value', 'default_value', 'options', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    // ── Accesseur typé ─────────────────────────────────────────────────────────

    public function getTypedValue(): mixed
    {
        $val = $this->value ?? $this->default_value;

        return match ($this->type) {
            'boolean' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
            'number'  => is_numeric($val) ? (float) $val : 0,
            default   => $val,
        };
    }

    // ── Méthodes statiques ─────────────────────────────────────────────────────

    /**
     * Récupère une valeur — avec cache 1h
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->getTypedValue() : $default;
        });
    }

    /**
     * Enregistre une valeur et invalide le cache
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    /**
     * Récupère toutes les clés d'un groupe sous forme key => value
     */
    public static function group(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return static::where('group', $group)
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(fn ($s) => [$s->key => $s->value ?? $s->default_value])
                ->toArray();
        });
    }

    /**
     * Invalide le cache d'un groupe entier
     */
    public static function clearGroupCache(string $group): void
    {
        Cache::forget("settings.group.{$group}");
        static::where('group', $group)
            ->pluck('key')
            ->each(fn ($key) => Cache::forget("setting.{$key}"));
    }
}
