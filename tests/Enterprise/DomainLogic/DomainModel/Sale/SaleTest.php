<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\DomainModel\Sale;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Sale::class)]
final class SaleTest extends TestCase
{
    #[Test]
    public function overTheCounterSaleChargesFullPriceAndReducesStock(): void
    {
        $apap = new Medication(ean: '5901234123457', name: 'Apap 500 mg', priceInCents: 749, stock: 10);

        $sale = Sale::overTheCounter($apap, quantity: 3, soldAt: new DateTimeImmutable('2026-10-18'));

        self::assertSame(3 * 749, $sale->totalInCents);
        self::assertNull($sale->prescriptionId);
        self::assertSame(7, $apap->stock);
    }

    #[Test]
    public function overTheCounterSaleRejectsQuantityAboveStock(): void
    {
        $apap = new Medication(ean: '5901234123457', name: 'Apap 500 mg', priceInCents: 749, stock: 2);

        $this->expectException(DispensingException::class);
        $this->expectExceptionMessage('requested 3, only 2 in stock');

        (void) Sale::overTheCounter($apap, quantity: 3, soldAt: new DateTimeImmutable('2026-10-18'));
    }
}
