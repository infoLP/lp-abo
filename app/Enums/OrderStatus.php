<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Brouillon = 'brouillon';
    case Validee   = 'validee';
    case Installee = 'installee';
    case Annulee   = 'annulee';

    public function label(): string
    {
        return match($this) {
            self::Brouillon => 'Brouillon',
            self::Validee   => 'Validée',
            self::Installee => 'Installée',
            self::Annulee   => 'Annulée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Brouillon => 'gray',
            self::Validee   => 'warning',
            self::Installee => 'success',
            self::Annulee   => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Brouillon => 'heroicon-o-pencil',
            self::Validee   => 'heroicon-o-check-circle',
            self::Installee => 'heroicon-o-star',
            self::Annulee   => 'heroicon-o-x-circle',
        };
    }
}
