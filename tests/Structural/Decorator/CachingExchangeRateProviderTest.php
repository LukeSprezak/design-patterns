<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Decorator;

use DateTimeImmutable;
use DesignPatterns\Structural\Decorator\CachingExchangeRateProvider;
use DesignPatterns\Structural\Decorator\Currency;
use DesignPatterns\Structural\Decorator\ExchangeRateProvider;
use DesignPatterns\Structural\Decorator\PrecedingBusinessDayExchangeRateProvider;
use DesignPatterns\Tests\Structural\Decorator\Fake\InMemoryNbpExchangeRateProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CachingExchangeRateProvider::class)]
#[CoversClass(PrecedingBusinessDayExchangeRateProvider::class)]
final class CachingExchangeRateProviderTest extends TestCase
{
    #[Test]
    public function nbpIsQueriedOncePerCurrencyAndDay(): void
    {
        $nbpExchangeRateProvider = InMemoryNbpExchangeRateProvider::withEaster2026Tables();
        $exchangeRateProvider = new CachingExchangeRateProvider($nbpExchangeRateProvider);
        $goodFriday = new DateTimeImmutable('2026-04-03');

        $firstEuroRate = $exchangeRateProvider->rateFor(Currency::Eur, $goodFriday);
        $secondEuroRate = $exchangeRateProvider->rateFor(Currency::Eur, $goodFriday);
        $exchangeRateProvider->rateFor(Currency::Usd, $goodFriday);

        self::assertSame($firstEuroRate, $secondEuroRate);
        self::assertSame(2, $nbpExchangeRateProvider->numberOfQueries);
    }

    /** @param \Closure(ExchangeRateProvider): ExchangeRateProvider $decorateNbpExchangeRateProvider */
    #[Test]
    #[DataProvider('decoratorOrders')]
    public function decoratorOrderDecidesWhatIsCached(\Closure $decorateNbpExchangeRateProvider, int $expectedNumberOfNbpQueries): void
    {
        $nbpExchangeRateProvider = InMemoryNbpExchangeRateProvider::withEaster2026Tables();
        $exchangeRateProvider = $decorateNbpExchangeRateProvider($nbpExchangeRateProvider);
        $tuesdayAfterEaster = new DateTimeImmutable('2026-04-07');

        $exchangeRateProvider->rateFor(Currency::Eur, $tuesdayAfterEaster);
        $exchangeRateProvider->rateFor(Currency::Eur, $tuesdayAfterEaster);

        self::assertSame($expectedNumberOfNbpQueries, $nbpExchangeRateProvider->numberOfQueries);
    }

    /** @return iterable<string, array{\Closure(ExchangeRateProvider): ExchangeRateProvider, int}> */
    public static function decoratorOrders(): iterable
    {
        yield 'cache outside remembers the whole walk back from Easter Monday to Good Friday' => [
            static fn (ExchangeRateProvider $nbpExchangeRateProvider): ExchangeRateProvider => new CachingExchangeRateProvider(
                new PrecedingBusinessDayExchangeRateProvider($nbpExchangeRateProvider),
            ),
            4,
        ];
        yield 'cache inside remembers only Good Friday, the days without a table are queried again' => [
            static fn (ExchangeRateProvider $nbpExchangeRateProvider): ExchangeRateProvider => new PrecedingBusinessDayExchangeRateProvider(
                new CachingExchangeRateProvider($nbpExchangeRateProvider),
            ),
            7,
        ];
    }
}
