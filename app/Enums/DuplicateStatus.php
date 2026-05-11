<?php

namespace App\Enums;

enum DuplicateStatus: string
{
    case Pending   = 'pending';
    case Merged    = 'merged';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'En attente',
            self::Merged    => 'Fusionné',
            self::Dismissed => 'Ignoré',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending   => 'warning',
            self::Merged    => 'success',
            self::Dismissed => 'gray',
        };
    }
}
