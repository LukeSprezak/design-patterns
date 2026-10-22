<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\ClassTable;

use PDO;

/** One table per class of the hierarchy, joined by the tracking number; no nullable subclass columns. */
final class ClassTableShipmentSchema
{
    public static function create(PDO $connection): void
    {
        $connection->exec(<<<SQL
            CREATE TABLE shipments (
                tracking_number TEXT PRIMARY KEY,
                type            TEXT NOT NULL,
                sender_name     TEXT NOT NULL
            );
            CREATE TABLE parcel_shipments (
                tracking_number TEXT PRIMARY KEY REFERENCES shipments (tracking_number),
                weight_in_grams INTEGER NOT NULL
            );
            CREATE TABLE pallet_shipments (
                tracking_number        TEXT PRIMARY KEY REFERENCES shipments (tracking_number),
                pallet_count           INTEGER NOT NULL,
                exchanges_euro_pallets INTEGER NOT NULL
            );
            SQL);
    }
}
