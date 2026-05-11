<?php
namespace App\Enums;
enum SupportType: string
{
    case PAPER = 'paper'; case DIGITAL = 'digital'; case COMBINED = 'combined';
    public function label(): string { return match($this) { self::PAPER => 'Papier', self::DIGITAL => 'Numerique', self::COMBINED => 'Papier + Numerique' }; }
}
