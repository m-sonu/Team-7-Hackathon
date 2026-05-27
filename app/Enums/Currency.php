<?php

namespace App\Enums;

enum Currency: string
{
    case NPR = 'NPR';
    case YEN = 'YEN';
    case USD = 'USD';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
