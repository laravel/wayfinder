<?php

namespace App\Enums;

enum QuotedEnum: string
{
    case Double = 'say "hi"';
    case Single = "it's here";
    case Backslash = 'back\\slash';
    case Newline = "line\nbreak";

    public function label(): string
    {
        return match ($this) {
            self::Double => 'A "quoted" label',
            self::Single => "It's a label",
            self::Backslash => 'back\\slash label',
            self::Newline => "multi\nline",
        };
    }
}
