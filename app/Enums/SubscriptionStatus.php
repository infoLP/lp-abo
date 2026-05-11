<?php
namespace App\Enums;
enum SubscriptionStatus: string
{
    case ACTIVE = 'active'; case EXPIRED = 'expired'; case SUSPENDED = 'suspended'; case CANCELLED = 'cancelled'; case PENDING = 'pending'; case TRIAL = 'trial';
    public function label(): string { return match($this) { self::ACTIVE => 'Actif', self::EXPIRED => 'Expire', self::SUSPENDED => 'Suspendu', self::CANCELLED => 'Annule', self::PENDING => 'En attente', self::TRIAL => 'Essai' }; }
    public function color(): string { return match($this) { self::ACTIVE => 'success', self::EXPIRED => 'danger', self::SUSPENDED => 'warning', self::CANCELLED => 'gray', self::PENDING => 'info', self::TRIAL => 'primary' }; }
}
