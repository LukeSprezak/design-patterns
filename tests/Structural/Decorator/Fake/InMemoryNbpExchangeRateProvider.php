<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Decorator\Fake;

use DateTimeImmutable;
use DesignPatterns\Structural\Decorator\Currency;
use DesignPatterns\Structural\Decorator\ExchangeRate;
use DesignPatterns\Structural\Decorator\ExchangeRateNotPublishedException;
use DesignPatterns\Structural\Decorator\ExchangeRateProvider;

final class InMemoryNbpExchangeRateProvider implements ExchangeRateProvider
{
    public private(set) int $numberOfQueries = 0;

    /** @param list<ExchangeRate> $publishedExchangeRates */
    public function __construct(
        private readonly array $publishedExchangeRates,
    ) {
    }

    /** NBP publishes on Good Friday (2026-04-03) but not over Easter weekend nor on Easter Monday (2026-04-06). */
    public static function withEaster2026Tables(): self
    {
        return new self([
            new ExchangeRate(Currency::Eur, new DateTimeImmutable('2026-04-02'), midRateInTenThousandths: 42683),
            new ExchangeRate(Currency::Eur, new DateTimeImmutable('2026-04-03'), midRateInTenThousandths: 42710),
            new ExchangeRate(Currency::Usd, new DateTimeImmutable('2026-04-03'), midRateInTenThousandths: 39124),
            new ExchangeRate(Currency::Eur, new DateTimeImmutable('2026-04-07'), midRateInTenThousandths: 42655),
        ]);
    }

    #[\Override]
    public function rateFor(Currency $currency, DateTimeImmutable $date): ExchangeRate
    {
        $this->numberOfQueries++;

        return array_find(
            $this->publishedExchangeRates,
            static fn (ExchangeRate $exchangeRate): bool => $exchangeRate->currency === $currency
                && $exchangeRate->effectiveDate->format('Y-m-d') === $date->format('Y-m-d'),
        ) ?? throw new ExchangeRateNotPublishedException("No NBP {$currency->name} rate published on {$date->format('Y-m-d')}");
    }
}
