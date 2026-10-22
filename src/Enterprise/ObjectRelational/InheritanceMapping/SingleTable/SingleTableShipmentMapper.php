<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\SingleTable;

use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\PalletShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\ParcelShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\Shipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\ShipmentType;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ShipmentMapper;
use PDO;

final readonly class SingleTableShipmentMapper implements ShipmentMapper
{
    private const string SELECT_COLUMNS = 'SELECT tracking_number, type, sender_name, weight_in_grams, pallet_count, exchanges_euro_pallets FROM shipments';

    public function __construct(private PDO $connection)
    {
    }

    #[\Override]
    public function insert(Shipment $shipment): void
    {
        $this->connection
            ->prepare(<<<SQL
                INSERT INTO shipments (tracking_number, type, sender_name, weight_in_grams, pallet_count, exchanges_euro_pallets)
                VALUES (?, ?, ?, ?, ?, ?)
                SQL)
            ->execute([
                $shipment->trackingNumber,
                ShipmentType::of($shipment)->value,
                $shipment->senderName,
                $shipment instanceof ParcelShipment ? $shipment->weightInGrams : null,
                $shipment instanceof PalletShipment ? $shipment->palletCount : null,
                $shipment instanceof PalletShipment ? (int) $shipment->exchangesEuroPallets : null,
            ]);
    }

    #[\Override]
    public function find(string $trackingNumber): ?Shipment
    {
        $statement = $this->connection->prepare(self::SELECT_COLUMNS . ' WHERE tracking_number = ?');
        $statement->execute([$trackingNumber]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[\Override]
    public function findBySender(string $senderName): array
    {
        $statement = $this->connection->prepare(self::SELECT_COLUMNS . ' WHERE sender_name = ? ORDER BY tracking_number');
        $statement->execute([$senderName]);

        return array_map($this->hydrate(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @param array<string, int|string|null> $row */
    private function hydrate(array $row): Shipment
    {
        return match (ShipmentType::from($row['type'])) {
            ShipmentType::Parcel => new ParcelShipment(
                trackingNumber: $row['tracking_number'],
                senderName: $row['sender_name'],
                weightInGrams: $row['weight_in_grams'],
            ),
            ShipmentType::Pallet => new PalletShipment(
                trackingNumber: $row['tracking_number'],
                senderName: $row['sender_name'],
                palletCount: $row['pallet_count'],
                exchangesEuroPallets: (bool) $row['exchanges_euro_pallets'],
            ),
        };
    }
}
