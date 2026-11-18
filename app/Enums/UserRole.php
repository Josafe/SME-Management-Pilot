<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case OfficeStaff = 'office_staff';
    case Technician = 'technician';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::OfficeStaff => 'Office staff',
            self::Technician => 'Technician',
        };
    }

    /**
     * Whether this role may create, edit and delete clients, contacts and addresses.
     */
    public function canManageClients(): bool
    {
        return match ($this) {
            self::Admin, self::OfficeStaff => true,
            self::Technician => false,
        };
    }
}
