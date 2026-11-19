<?php

namespace App\Support;

final class Money
{
    public static function multiply(string|int|float $amount, int $quantity): string
    {
        return bcmul((string) $amount, (string) $quantity, 2);
    }

    public static function add(string|int|float $left, string|int|float $right): string
    {
        return bcadd((string) $left, (string) $right, 2);
    }

    public static function compare(string|int|float $left, string|int|float $right): int
    {
        return bccomp((string) $left, (string) $right, 2);
    }

    public static function subtract(string|int|float $left, string|int|float $right): string
    {
        return bcsub((string) $left, (string) $right, 2);
    }
}
