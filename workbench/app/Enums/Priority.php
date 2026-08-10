<?php

namespace App\Enums;

enum Priority: int
{
    case Low = 1;
    case High = 2;

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::High => 'High',
        };
    }

    public function multiplier(): float
    {
        return match ($this) {
            self::Low => 0.5,
            self::High => 2.5,
        };
    }

    public function stage(): UnitEnum
    {
        return UnitEnum::Open;
    }
}
