<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\InheritanceMapping;

use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ClassTable\ClassTableShipmentMapper;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ClassTable\ClassTableShipmentSchema;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\PalletShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\ParcelShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ShipmentMapper;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\SingleTable\SingleTableShipmentMapper;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\SingleTable\SingleTableShipmentSchema;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** The same behaviour is expected from both inheritance mappings; only the table layout differs. */
#[CoversClass(SingleTableShipmentMapper::class)]
#[CoversClass(ClassTableShipmentMapper::class)]
#[CoversClass(ParcelShipment::class)]
#[CoversClass(PalletShipment::class)]
final class ShipmentMapperTest extends TestCase
{
    /** @return iterable<string, array{ShipmentMapper}> */
    public static function mappers(): iterable
    {
        $singleTableConnection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        SingleTableShipmentSchema::create($singleTableConnection);
        yield 'single table inheritance' => [new SingleTableShipmentMapper($singleTableConnection)];

        $classTableConnection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        ClassTableShipmentSchema::create($classTableConnection);
        yield 'class table inheritance' => [new ClassTableShipmentMapper($classTableConnection)];
    }

    #[Test]
    #[DataProvider('mappers')]
    public function findReturnsTheConcreteSubclassWithItsOwnFields(ShipmentMapper $shipments): void
    {
        $shipments->insert(new ParcelShipment(trackingNumber: 'PARCEL-0001', senderName: 'Neuca', weightInGrams: 2300));
        $shipments->insert(new PalletShipment(trackingNumber: 'PALLET-0001', senderName: 'Neuca', palletCount: 3, exchangesEuroPallets: true));

        $parcel = $shipments->find('PARCEL-0001');
        $pallet = $shipments->find('PALLET-0001');

        self::assertInstanceOf(ParcelShipment::class, $parcel);
        self::assertSame(2300, $parcel->weightInGrams);
        self::assertInstanceOf(PalletShipment::class, $pallet);
        self::assertSame(3, $pallet->palletCount);
        self::assertTrue($pallet->exchangesEuroPallets);
        self::assertNull($shipments->find('PARCEL-9999'));
    }

    #[Test]
    #[DataProvider('mappers')]
    public function findBySenderReturnsMixedSubclassesReadyForPolymorphicPricing(ShipmentMapper $shipments): void
    {
        $shipments->insert(new ParcelShipment(trackingNumber: 'PARCEL-0001', senderName: 'Neuca', weightInGrams: 2300));
        $shipments->insert(new PalletShipment(trackingNumber: 'PALLET-0001', senderName: 'Neuca', palletCount: 3, exchangesEuroPallets: false));
        $shipments->insert(new ParcelShipment(trackingNumber: 'PARCEL-0002', senderName: 'Farmacol', weightInGrams: 500));

        $neucaShipments = $shipments->findBySender('Neuca');

        self::assertSame(['PALLET-0001', 'PARCEL-0001'], array_map(static fn ($shipment) => $shipment->trackingNumber, $neucaShipments));
        self::assertSame(
            2100 + 48000,
            array_sum(array_map(static fn ($shipment) => $shipment->priceInCents(), $neucaShipments)),
        );
    }
}
