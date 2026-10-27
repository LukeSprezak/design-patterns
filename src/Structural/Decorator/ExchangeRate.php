<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Decorator;

use DateTimeImmutable;

final readonly class ExchangeRate
{
    public function __construct(
        public Currency $currency,
        public DateTimeImmutable $effectiveDate,
        public int $midRateInTenThousandths,
    ) {
    }
}
