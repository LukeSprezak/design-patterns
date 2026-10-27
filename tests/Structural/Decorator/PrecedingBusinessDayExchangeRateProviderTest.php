<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Decorator;

use DateTimeImmutable;
use DesignPatterns\Structural\Decorator\Currency;
use DesignPatterns\Structural\Decorator\PrecedingBusinessDayExchangeRateProvider;
use DesignPatterns\Tests\Structural\Decorator\Fake\InMemoryNbpExchangeRateProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrecedingBusinessDayExchangeRateProvider::class)]
final class PrecedingBusinessDayExchangeRateProviderTest extends TestCase
{
    #[Test]
    #[DataProvider('invoiceDates')]
    public function rateComesFromLastTablePublishedBeforeInvoiceDate(string $invoiceDate, string $expectedEffectiveDate): void
    {
        $exchangeRateProvider = new PrecedingBusinessDayExchangeRateProvider(InMemoryNbpExchangeRateProvider::withEaster2026Tables());

        $exchangeRate = $exchangeRateProvider->rateFor(Currency::Eur, new DateTimeImmutable($invoiceDate));

        self::assertSame($expectedEffectiveDate, $exchangeRate->effectiveDate->format('Y-m-d'));
    }

    /** @return iterable<string, array{string, string}> */
    public static function invoiceDates(): iterable
    {
        yield 'Good Friday takes Thursday rate' => ['2026-04-03', '2026-04-02'];
        yield 'Easter Saturday takes Good Friday rate' => ['2026-04-04', '2026-04-03'];
        yield 'Tuesday after Easter skips own table and the holiday' => ['2026-04-07', '2026-04-03'];
    }
}
