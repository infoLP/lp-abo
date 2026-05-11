<?php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name', 'first_name', 'last_name', 'email',
        'password', 'phone', 'is_active', 'client_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Un compte désactivé ne peut jamais accéder à quoi que ce soit
        if (!$this->is_active) {
            return false;
        }

        return $panel->getId() === 'admin'
            ? $this->hasAnyRole(['admin', 'director', 'manager', 'accountant'])
            : true;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
