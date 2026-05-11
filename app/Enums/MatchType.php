<?php

namespace App\Enums;

enum MatchType: string
{
    case Email       = 'email';
    case Siret       = 'siret';
    case NamePostal  = 'name_postal';
    case Phone       = 'phone';
    case CompanyCity = 'company_city';

    public function label(): string
    {
        return match ($this) {
            self::Email       => 'Email identique',
            self::Siret       => 'SIRET identique',
            self::NamePostal  => 'Nom + Prénom + CP',
            self::Phone       => 'Téléphone identique',
            self::CompanyCity => 'Raison sociale + Ville',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Email       => 'heroicon-o-envelope',
            self::Siret       => 'heroicon-o-building-office',
            self::NamePostal  => 'heroicon-o-user',
            self::Phone       => 'heroicon-o-phone',
            self::CompanyCity => 'heroicon-o-building-storefront',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Email       => 'danger',
            self::Siret       => 'danger',
            self::NamePostal  => 'warning',
            self::Phone       => 'info',
            self::CompanyCity => 'gray',
        };
    }
}
