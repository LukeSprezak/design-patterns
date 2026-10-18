<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\DomainModel;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\RefundLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefundLevel::class)]
final class RefundLevelTest extends TestCase
{
    #[Test]
    #[DataProvider('refundLevelsWithPatientPrice')]
    public function calculatesPatientPricePerPackage(
        RefundLevel $refundLevel,
        int $fullPriceInCents,
        int $expectedPatientPriceInCents,
    ): void {
        self::assertSame($expectedPatientPriceInCents, $refundLevel->patientPriceInCents($fullPriceInCents));
    }

    /** @return iterable<string, array{RefundLevel, int, int}> */
    public static function refundLevelsWithPatientPrice(): iterable
    {
        yield 'free' => [RefundLevel::Free, 5000, 0];
        yield 'flat fee regardless of price' => [RefundLevel::FlatFee, 12000, 320];
        yield '30 percent' => [RefundLevel::ThirtyPercent, 1000, 300];
        yield '50 percent' => [RefundLevel::FiftyPercent, 2000, 1000];
        yield 'full price' => [RefundLevel::Full, 749, 749];
    }
}
