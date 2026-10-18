<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription;

enum RefundLevel: string
{
    private const int FLAT_FEE_IN_CENTS = 320;

    case Free = 'B';
    case FlatFee = 'R';
    case ThirtyPercent = '30';
    case FiftyPercent = '50';
    case Full = '100';

    public function patientPriceInCents(int $fullPriceInCents): int
    {
        return match ($this) {
            self::Free => 0,
            self::FlatFee => self::FLAT_FEE_IN_CENTS,
            self::ThirtyPercent => intdiv($fullPriceInCents * 30, 100),
            self::FiftyPercent => intdiv($fullPriceInCents * 50, 100),
            self::Full => $fullPriceInCents,
        };
    }
}
