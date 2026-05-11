<?php
namespace App\Enums;
enum SubscriptionMode: string
{
    case DURATION = 'duration'; case ISSUES = 'issues';
    public function label(): string { return match($this) { self::DURATION => 'Par duree', self::ISSUES => 'Par nombre de numeros' }; }
}
