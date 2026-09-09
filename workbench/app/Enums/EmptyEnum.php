<?php

namespace App\Enums;

enum EmptyEnum: string
{
    public function label(): string
    {
        return 'Nothing';
    }
}
