<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\AbstractFactory;

use DesignPatterns\Creational\AbstractFactory\InPostCarrierFactory;
use DesignPatterns\Creational\AbstractFactory\Parcel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InPostCarrierFactory::class)]
final class InPostCarrierFactoryTest extends TestCase
{
    #[Test]
    public function generatesTwentyFourDigitTrackingNumber(): void
    {
        $trackingNumber = new InPostCarrierFactory()->createTrackingNumberGenerator()->generate();

        self::assertMatchesRegularExpression('/^\d{24}$/', $trackingNumber);
    }

    #[Test]
    #[DataProvider('parcelsWithExpectedCost')]
    public function calculatesCostByLockerSize(Parcel $parcel, int $expectedCostInCents): void
    {
        $costInCents = new InPostCarrierFactory()->createShippingCostCalculator()->calculate($parcel);

        self::assertSame($expectedCostInCents, $costInCents);
    }

    #[Test]
    #[DataProvider('parcelsWithExpectedSize')]
    public function labelShowsLockerSizeAndTrackingNumber(Parcel $parcel, string $expectedSize): void
    {
        $label = new InPostCarrierFactory()
            ->createShippingLabelGenerator()
            ->generate($parcel, '123456789012345678901234');

        self::assertStringContainsString('InPost', $label);
        self::assertStringContainsString('123456789012345678901234', $label);
        self::assertStringContainsString("Size: {$expectedSize}", $label);
        self::assertStringContainsString('Jan Kowalski', $label);
    }

    /** @return iterable<string, array{Parcel, string}> */
    public static function parcelsWithExpectedSize(): iterable
    {
        yield 'flat envelope fits size A' => [self::parcelWithDimensions(64, 38, 8), 'A'];
        yield 'shoe box fits size B' => [self::parcelWithDimensions(30, 20, 15), 'B'];
        yield 'rotated box still fits size B' => [self::parcelWithDimensions(15, 30, 20), 'B'];
        yield 'large box needs size C' => [self::parcelWithDimensions(60, 35, 40), 'C'];
    }

    /** @return iterable<string, array{Parcel, int}> */
    public static function parcelsWithExpectedCost(): iterable
    {
        yield 'size A' => [self::parcelWithDimensions(64, 38, 8), 1299];
        yield 'size B' => [self::parcelWithDimensions(30, 20, 15), 1499];
        yield 'size C' => [self::parcelWithDimensions(60, 35, 40), 1899];
    }

    private static function parcelWithDimensions(int $length, int $width, int $height): Parcel
    {
        return new Parcel(
            weightInGrams: 1000,
            lengthInCentimeters: $length,
            widthInCentimeters: $width,
            heightInCentimeters: $height,
            recipientName: 'Jan Kowalski',
            deliveryAddress: 'ul. Prosta 1, 00-001 Warszawa',
        );
    }
}
