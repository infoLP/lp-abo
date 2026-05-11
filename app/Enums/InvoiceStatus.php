<?php
namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft     = 'draft';
    case Sent      = 'sent';
    case Paid      = 'paid';
    case Overdue   = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Brouillon',
            self::Sent      => 'Envoyée',
            self::Paid      => 'Payée',
            self::Overdue   => 'En retard',
            self::Cancelled => 'Annulée',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft     => 'gray',
            self::Sent      => 'info',
            self::Paid      => 'success',
            self::Overdue   => 'danger',
            self::Cancelled => 'warning',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Draft     => 'heroicon-o-pencil',
            self::Sent      => 'heroicon-o-paper-airplane',
            self::Paid      => 'heroicon-o-check-circle',
            self::Overdue   => 'heroicon-o-exclamation-circle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }
}
