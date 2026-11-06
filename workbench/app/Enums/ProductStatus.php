<?php

namespace App\Enums;

enum ProductStatus: string
{
    case new = 'new';
    case used = 'used';
    case for = 'for-sale';
    case Active = 'active';

    public function label(): string
    {
        return match ($this) {
            self::new => 'Brand New',
            self::used => 'Pre-Owned',
            self::for => 'For Sale',
            self::Active => 'Active',
        };
    }

    public function default(): bool
    {
        return $this === self::Active;
    }
}
