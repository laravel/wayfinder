<?php

namespace App\Enums;

enum EscapedStatus: string
{
    case LiteralBackslash = '\\';
    case LiteralBacktick = "`";
    case Quote = 'they said "yes"';
    case StartsWithQuote = '"quoted"';
    case Backtick = '`template`';
    case Backslash = 'path\\to\\file';
    case Newline = "line\nbreak";
}
