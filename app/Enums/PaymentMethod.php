<?php
namespace App\Enums;
enum PaymentMethod: string
{
    case CARD = 'card'; case SEPA = 'sepa'; case CHECK = 'check'; case TRANSFER = 'transfer'; case FREE = 'free';
    public function label(): string { return match($this) { self::CARD => 'Carte bancaire', self::SEPA => 'Prelevement SEPA', self::CHECK => 'Cheque', self::TRANSFER => 'Virement', self::FREE => 'Gratuit' }; }
}
