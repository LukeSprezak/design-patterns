<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\EmbeddedValue;

use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\Currency;
use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\CurrencyMismatchException;
use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\Money;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Money::class)]
#[CoversClass(CurrencyMismatchException::class)]
final class MoneyTest extends TestCase
{
    #[Test]
    public function addsAmountsOfTheSameCurrency(): void
    {
        $sum = new Money(10000, Currency::Pln)->add(new Money(2300, Currency::Pln));

        self::assertEquals(new Money(12300, Currency::Pln), $sum);
    }

    #[Test]
    public function refusesToAddDifferentCurrencies(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        new Money(10000, Currency::Pln)->add(new Money(2300, Currency::Eur));
    }
}
