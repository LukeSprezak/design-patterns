<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\AbstractFactory\InPost;

use DesignPatterns\Creational\AbstractFactory\InPost\InPostCarrierFactory;
use DesignPatterns\Creational\AbstractFactory\InPost\ParcelDoesNotFitLockerException;
use DesignPatterns\Creational\AbstractFactory\Parcel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

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
    public function generatesSameTrackingNumberForSameRandomSeed(): void
    {
        $firstTrackingNumber = new InPostCarrierFactory(new Randomizer(new Mt19937(seed: 42)))
            ->createTrackingNumberGenerator()
            ->generate();
        $secondTrackingNumber = new InPostCarrierFactory(new Randomizer(new Mt19937(seed: 42)))
            ->createTrackingNumberGenerator()
            ->generate();

        self::assertSame($firstTrackingNumber, $secondTrackingNumber);
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

    #[Test]
    #[DataProvider('parcelsExceedingLockerDimensions')]
    public function rejectsParcelThatDoesNotFitAnyLocker(Parcel $parcel): void
    {
        $this->expectException(ParcelDoesNotFitLockerException::class);

        new InPostCarrierFactory()->createShippingCostCalculator()->calculate($parcel);
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

    /** @return iterable<string, array{Parcel}> */
    public static function parcelsExceedingLockerDimensions(): iterable
    {
        yield 'too long' => [self::parcelWithDimensions(70, 30, 10)];
        yield 'too wide even though flat' => [self::parcelWithDimensions(64, 45, 8)];
        yield 'too deep' => [self::parcelWithDimensions(60, 38, 42)];
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
