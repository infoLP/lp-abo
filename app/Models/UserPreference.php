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
     * Récupérer une préférence pour l'utilisateur connecté
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (! auth()->check()) return $default;

        $pref = static::where('user_id', auth()->id())
                      ->where('key', $key)
                      ->first();

        return $pref ? $pref->value : $default;
    }

    /**
     * Enregistrer une préférence pour l'utilisateur connecté
     */
    public static function set(string $key, mixed $value): void
    {
        if (! auth()->check()) return;

        static::updateOrCreate(
            ['user_id' => auth()->id(), 'key' => $key],
            ['value'   => $value]
        );
    }
}
