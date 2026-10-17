<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\AbstractFactory;

use DesignPatterns\Creational\AbstractFactory\DhlCarrierFactory;
use DesignPatterns\Creational\AbstractFactory\Parcel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DhlCarrierFactory::class)]
final class DhlCarrierFactoryTest extends TestCase
{
    #[Test]
    public function generatesTrackingNumberWithJdPrefixAndEighteenDigits(): void
    {
        $trackingNumber = new DhlCarrierFactory()->createTrackingNumberGenerator()->generate();

        self::assertMatchesRegularExpression('/^JD\d{18}$/', $trackingNumber);
    }

    #[Test]
    #[DataProvider('parcelsWithExpectedCost')]
    public function calculatesCostByWeightBand(Parcel $parcel, int $expectedCostInCents): void
    {
        $costInCents = new DhlCarrierFactory()->createShippingCostCalculator()->calculate($parcel);

        self::assertSame($expectedCostInCents, $costInCents);
    }

    #[Test]
    public function labelShowsWeightInKilogramsAndTrackingNumber(): void
    {
        $label = new DhlCarrierFactory()
            ->createShippingLabelGenerator()
            ->generate(self::parcelWithWeight(2500), 'JD123456789012345678');

        self::assertStringContainsString('DHL', $label);
        self::assertStringContainsString('JD123456789012345678', $label);
        self::assertStringContainsString('Weight: 2.50 kg', $label);
        self::assertStringContainsString('Jan Kowalski', $label);
    }

    /** @return iterable<string, array{Parcel, int}> */
    public static function parcelsWithExpectedCost(): iterable
    {
        yield 'up to 1 kg' => [self::parcelWithWeight(1000), 1500];
        yield 'up to 5 kg' => [self::parcelWithWeight(4999), 1900];
        yield 'up to 10 kg' => [self::parcelWithWeight(10000), 2500];
        yield 'over 10 kg' => [self::parcelWithWeight(20000), 3500];
    }

    private static function parcelWithWeight(int $weightInGrams): Parcel
    {
        return new Parcel(
            weightInGrams: $weightInGrams,
            lengthInCentimeters: 30,
            widthInCentimeters: 20,
            heightInCentimeters: 15,
            recipientName: 'Jan Kowalski',
            deliveryAddress: 'ul. Prosta 1, 00-001 Warszawa',
        );
    }
}
