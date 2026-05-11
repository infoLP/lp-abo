<?php
namespace App\Enums;
enum ClientStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';
    public function label(): string { return match($this) { self::ACTIVE => 'Actif', self::INACTIVE => 'Inactif', self::ARCHIVED => 'Archive' }; }
    public function color(): string { return match($this) { self::ACTIVE => 'success', self::INACTIVE => 'warning', self::ARCHIVED => 'danger' }; }
    public function icon(): string { return match($this) { self::ACTIVE => 'heroicon-o-check-circle', self::INACTIVE => 'heroicon-o-pause-circle', self::ARCHIVED => 'heroicon-o-archive-box' }; }
}
