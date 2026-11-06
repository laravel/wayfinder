<?php

namespace App\Enums\Concerns;

trait HasIcon
{
    public function icon(): string
    {
        return 'icon-'.$this->value;
    }
}
