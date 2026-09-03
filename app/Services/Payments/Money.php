<?php

namespace App\Services\Payments;

final class Money
{
    public static function toMinor(float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    public static function toMajor(int $minor): float
    {
        return round($minor / 100, 2);
    }
}
