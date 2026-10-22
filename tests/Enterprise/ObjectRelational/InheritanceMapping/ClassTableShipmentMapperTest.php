<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\InheritanceMapping;

use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ClassTable\ClassTableShipmentMapper;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ClassTable\ClassTableShipmentSchema;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\PalletShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\ParcelShipment;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassTableShipmentMapper::class)]
#[CoversClass(ClassTableShipmentSchema::class)]
final class ClassTableShipmentMapperTest extends TestCase
{
    #[Test]
    public function eachClassOfTheHierarchyGetsItsOwnTableSharingTheTrackingNumber(): void
    {
        $connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        ClassTableShipmentSchema::create($connection);
        $shipments = new ClassTableShipmentMapper($connection);

        $shipments->insert(new ParcelShipment(trackingNumber: 'PARCEL-0001', senderName: 'Neuca', weightInGrams: 2300));
        $shipments->insert(new PalletShipment(trackingNumber: 'PALLET-0001', senderName: 'Neuca', palletCount: 3, exchangesEuroPallets: true));

        self::assertSame(
            [['tracking_number' => 'PALLET-0001', 'type' => 'pallet'], ['tracking_number' => 'PARCEL-0001', 'type' => 'parcel']],
            $connection->query('SELECT tracking_number, type FROM shipments ORDER BY tracking_number')->fetchAll(PDO::FETCH_ASSOC),
        );
        self::assertSame(
            [['tracking_number' => 'PARCEL-0001', 'weight_in_grams' => 2300]],
            $connection->query('SELECT tracking_number, weight_in_grams FROM parcel_shipments')->fetchAll(PDO::FETCH_ASSOC),
        );
        self::assertSame(
            [['tracking_number' => 'PALLET-0001', 'pallet_count' => 3, 'exchanges_euro_pallets' => 1]],
            $connection->query('SELECT tracking_number, pallet_count, exchanges_euro_pallets FROM pallet_shipments')->fetchAll(PDO::FETCH_ASSOC),
        );
    }
}
