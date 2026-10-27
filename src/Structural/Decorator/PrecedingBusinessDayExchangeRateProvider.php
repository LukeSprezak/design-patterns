<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Decorator;

use DateTimeImmutable;

/**
 * Art. 31a of the Polish VAT Act: foreign currency amounts are converted at the NBP mid rate
 * from the last business day preceding the invoice date.
 */
final readonly class PrecedingBusinessDayExchangeRateProvider implements ExchangeRateProvider
{
    public function __construct(
        private ExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    #[\Override]
    public function rateFor(Currency $currency, DateTimeImmutable $date): ExchangeRate
    {
        return $this->lastRatePublishedOnOrBefore($currency, $date->modify('-1 day'));
    }

    private function lastRatePublishedOnOrBefore(Currency $currency, DateTimeImmutable $date): ExchangeRate
    {
        try {
            return $this->exchangeRateProvider->rateFor($currency, $date);
        } catch (ExchangeRateNotPublishedException) {
            return $this->lastRatePublishedOnOrBefore($currency, $date->modify('-1 day'));
        }
    }
}
