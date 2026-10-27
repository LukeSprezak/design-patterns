<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Decorator;

use DateTimeImmutable;

interface ExchangeRateProvider
{
    /** @throws ExchangeRateNotPublishedException */
    public function rateFor(Currency $currency, DateTimeImmutable $date): ExchangeRate;
}
