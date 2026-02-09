<?php

namespace Gcorpllc\Paypey\Classes;

class Currency
{
    public const RIAL = 'IRR';
    public const TOMAN = 'IRT';
    public const USD = 'USD';
    public const EUR = 'EUR';

    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        // IRR to IRT
        if ($from === self::RIAL && $to === self::TOMAN) {
            return $amount / 10;
        }

        // IRT to IRR
        if ($from === self::TOMAN && $to === self::RIAL) {
            return $amount * 10;
        }

        // For other currencies, we might need an exchange rate service.
        // For now, we return as is or handle specific cases.
        return $amount;
    }
}
