<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\InheritanceMapping;

use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ClassTable\ClassTableShipmentMapper;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ClassTable\ClassTableShipmentSchema;
use DesignPatterns\Enterprise\DataSource\DataMapper\PdoTransactionManager;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\PalletShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\ParcelShipment;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassTableShipmentMapper::class)]
#[CoversClass(ClassTableShipmentSchema::class)]
final class ClassTableShipmentMapperTest extends TestCase
{
    private PDO $connection;
    private ClassTableShipmentMapper $shipments;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        ClassTableShipmentSchema::create($this->connection);
        $this->shipments = new ClassTableShipmentMapper($this->connection, new PdoTransactionManager($this->connection));
    }

    #[Test]
    public function eachClassOfTheHierarchyGetsItsOwnTableSharingTheTrackingNumber(): void
    {
        $shipments = $this->shipments;
        $connection = $this->connection;

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

    #[Test]
    public function insertRollsBackTheBaseRowWhenTheSubclassRowCannotBeWritten(): void
    {
        $this->connection->exec('DROP TABLE parcel_shipments');

        try {
            $this->shipments->insert(new ParcelShipment(trackingNumber: 'PARCEL-0001', senderName: 'Neuca', weightInGrams: 2300));
            self::fail('Inserting the subclass row into a missing table must throw');
        } catch (PDOException) {
        }

        self::assertSame(0, $this->connection->query('SELECT COUNT(*) FROM shipments')->fetchColumn());
    }
}
