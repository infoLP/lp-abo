<?php
namespace App\Enums;
enum ContactStatus: string
{
    case NEW = 'new'; case READ = 'read'; case REPLIED = 'replied'; case CLOSED = 'closed';
    public function label(): string { return match($this) { self::NEW => 'Nouveau', self::READ => 'Lu', self::REPLIED => 'Repondu', self::CLOSED => 'Ferme' }; }
    public function color(): string { return match($this) { self::NEW => 'danger', self::READ => 'info', self::REPLIED => 'success', self::CLOSED => 'gray' }; }
}
