<?php

namespace App\Enums;

enum AiProcessStatus: string
{
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    public static function all(): array
    {
        return [self::PROCESSING->value, self::SUCCESS->value, self::FAILED->value];
    }
}
