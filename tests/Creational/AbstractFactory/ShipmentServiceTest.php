<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\AbstractFactory;

use DesignPatterns\Creational\AbstractFactory\CarrierFactory;
use DesignPatterns\Creational\AbstractFactory\DhlCarrierFactory;
use DesignPatterns\Creational\AbstractFactory\InPostCarrierFactory;
use DesignPatterns\Creational\AbstractFactory\Parcel;
use DesignPatterns\Creational\AbstractFactory\ShipmentService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShipmentService::class)]
final class ShipmentServiceTest extends TestCase
{
    #[Test]
    #[DataProvider('carriers')]
    public function dispatchedShipmentUsesProductsOfOneCarrierFamily(
        CarrierFactory $carrierFactory,
        string $expectedTrackingNumberPattern,
        string $expectedCarrierNameOnLabel,
        int $expectedCostInCents,
    ): void {
        $parcel = new Parcel(
            weightInGrams: 2500,
            lengthInCentimeters: 30,
            widthInCentimeters: 20,
            heightInCentimeters: 15,
            recipientName: 'Jan Kowalski',
            deliveryAddress: 'ul. Prosta 1, 00-001 Warszawa',
        );

        $shipment = new ShipmentService($carrierFactory)->dispatch($parcel);

        self::assertMatchesRegularExpression($expectedTrackingNumberPattern, $shipment->trackingNumber);
        self::assertStringContainsString($shipment->trackingNumber, $shipment->label);
        self::assertStringContainsString($expectedCarrierNameOnLabel, $shipment->label);
        self::assertSame($expectedCostInCents, $shipment->costInCents);
    }

    /** @return iterable<string, array{CarrierFactory, string, string, int}> */
    public static function carriers(): iterable
    {
        yield 'InPost' => [new InPostCarrierFactory(), '/^\d{24}$/', 'InPost', 1499];
        yield 'DHL' => [new DhlCarrierFactory(), '/^JD\d{18}$/', 'DHL', 1900];
    }
}
