<?php

namespace App\Enums;

enum ProductStatus: string
{
    case new = 'new';
    case used = 'used';
    case for = 'for-sale';
    case Active = 'active';
}
