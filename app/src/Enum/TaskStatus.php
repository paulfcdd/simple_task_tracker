<?php

namespace App\Enum;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case DONE = 'done';

    public static function tryFromString(string $status): ?self
    {
        return self::tryFrom(strtolower($status));
    }
}
