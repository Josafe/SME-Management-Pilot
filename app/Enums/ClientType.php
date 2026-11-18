<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ClientType: string implements HasLabel
{
    case Company = 'company';
    case Individual = 'individual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Company => 'Company',
            self::Individual => 'Individual',
        };
    }
}
