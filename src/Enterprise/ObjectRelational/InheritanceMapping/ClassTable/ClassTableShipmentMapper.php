<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ClassTable;

use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\PalletShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\ParcelShipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\Shipment;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\ShipmentType;
use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ShipmentMapper;
use PDO;

final readonly class ClassTableShipmentMapper implements ShipmentMapper
{
    /** Every read joins all subclass tables; the type column says which joined columns are meaningful. */
    private const string SELECT_JOINED = <<<SQL
        SELECT shipments.tracking_number, shipments.type, shipments.sender_name,
               parcel_shipments.weight_in_grams,
               pallet_shipments.pallet_count, pallet_shipments.exchanges_euro_pallets
        FROM shipments
        LEFT JOIN parcel_shipments ON parcel_shipments.tracking_number = shipments.tracking_number
        LEFT JOIN pallet_shipments ON pallet_shipments.tracking_number = shipments.tracking_number
        SQL;

    public function __construct(private PDO $connection)
    {
    }

    #[\Override]
    public function insert(Shipment $shipment): void
    {
        $this->connection
            ->prepare('INSERT INTO shipments (tracking_number, type, sender_name) VALUES (?, ?, ?)')
            ->execute([$shipment->trackingNumber, ShipmentType::of($shipment)->value, $shipment->senderName]);

        match (true) {
            $shipment instanceof ParcelShipment => $this->connection
                ->prepare('INSERT INTO parcel_shipments (tracking_number, weight_in_grams) VALUES (?, ?)')
                ->execute([$shipment->trackingNumber, $shipment->weightInGrams]),
            $shipment instanceof PalletShipment => $this->connection
                ->prepare('INSERT INTO pallet_shipments (tracking_number, pallet_count, exchanges_euro_pallets) VALUES (?, ?, ?)')
                ->execute([$shipment->trackingNumber, $shipment->palletCount, (int) $shipment->exchangesEuroPallets]),
        };
    }

    #[\Override]
    public function find(string $trackingNumber): ?Shipment
    {
        $statement = $this->connection->prepare(self::SELECT_JOINED . ' WHERE shipments.tracking_number = ?');
        $statement->execute([$trackingNumber]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[\Override]
    public function findBySender(string $senderName): array
    {
        $statement = $this->connection->prepare(self::SELECT_JOINED . ' WHERE shipments.sender_name = ? ORDER BY shipments.tracking_number');
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
