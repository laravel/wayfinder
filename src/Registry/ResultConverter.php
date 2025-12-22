<?php

namespace Laravel\Wayfinder\Registry;

use Laravel\Surveyor\Types\Contracts\Type;

class ResultConverter
{
    protected static ?ConverterRegistry $registry = null;

    public static function getRegistry(): ConverterRegistry
    {
        if (static::$registry === null) {
            static::$registry = new ConverterRegistry;
        }

        return static::$registry;
    }

    public static function to(Type $result, string $converter): string
    {
        return static::getRegistry()->convert($result, $converter);
    }

    public static function register(string $converter): void
    {
        static::getRegistry()->register($converter);
    }
}
