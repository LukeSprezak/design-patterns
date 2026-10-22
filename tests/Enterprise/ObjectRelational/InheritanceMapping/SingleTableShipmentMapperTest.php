<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\InheritanceMapping;

use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\PalletShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\ParcelShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\SingleTable\SingleTableShipmentMapper;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\SingleTable\SingleTableShipmentSchema;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SingleTableShipmentMapper::class)]
#[CoversClass(SingleTableShipmentSchema::class)]
final class SingleTableShipmentMapperTest extends TestCase
{
    #[Test]
    public function bothSubclassesShareOneTableWithNullsInTheOtherSubclassColumns(): void
    {
        $connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        SingleTableShipmentSchema::create($connection);
        $shipments = new SingleTableShipmentMapper($connection);

        $shipments->insert(new ParcelShipment(trackingNumber: 'PARCEL-0001', senderName: 'Neuca', weightInGrams: 2300));
        $shipments->insert(new PalletShipment(trackingNumber: 'PALLET-0001', senderName: 'Neuca', palletCount: 3, exchangesEuroPallets: true));

        self::assertSame(
            [
                ['type' => 'pallet', 'weight_in_grams' => null, 'pallet_count' => 3, 'exchanges_euro_pallets' => 1],
                ['type' => 'parcel', 'weight_in_grams' => 2300, 'pallet_count' => null, 'exchanges_euro_pallets' => null],
            ],
            $connection
                ->query('SELECT type, weight_in_grams, pallet_count, exchanges_euro_pallets FROM shipments ORDER BY tracking_number')
                ->fetchAll(PDO::FETCH_ASSOC),
        );
    }
}
