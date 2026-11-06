<?php

namespace App\Enums;

enum UnitEnum
{
    case None;
    case Open;
    case Done;

    public function label(): string
    {
        return match ($this) {
            self::None => 'Not Started',
            self::Open => 'In Progress',
            self::Done => 'Finished',
        };
    }
}
