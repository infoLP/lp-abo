<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = ['user_id', 'key', 'value'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Récupérer une préférence.
     * $userId optionnel pour compatibilité CLI/jobs (auth() indisponible hors requête HTTP).
     */
    public static function get(string $key, mixed $default = null, ?int $userId = null): mixed
    {
        $userId = $userId ?? (auth()->check() ? auth()->id() : null);
        if (! $userId) return $default;

        $pref = static::where('user_id', $userId)
                      ->where('key', $key)
                      ->first();

        return $pref ? $pref->value : $default;
    }

    /**
     * Enregistrer une préférence.
     * $userId optionnel pour compatibilité CLI/jobs.
     */
    public static function set(string $key, mixed $value, ?int $userId = null): void
    {
        $userId = $userId ?? (auth()->check() ? auth()->id() : null);
        if (! $userId) return;

        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value'   => $value]
        );
    }
}
