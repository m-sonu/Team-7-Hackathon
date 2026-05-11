<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'Admin';
    case EMPLOYEE = 'Employee';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::EMPLOYEE => 'Employee',
        };
    }
}
