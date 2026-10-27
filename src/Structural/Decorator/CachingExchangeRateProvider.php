<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Decorator;

use DateTimeImmutable;

final class CachingExchangeRateProvider implements ExchangeRateProvider
{
    /** @var array<string, ExchangeRate> */
    private array $exchangeRatesByCurrencyAndDate = [];

    public function __construct(
        private readonly ExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    #[\Override]
    public function rateFor(Currency $currency, DateTimeImmutable $date): ExchangeRate
    {
        return $this->exchangeRatesByCurrencyAndDate["{$currency->name} {$date->format('Y-m-d')}"]
            ??= $this->exchangeRateProvider->rateFor($currency, $date);
    }
}
