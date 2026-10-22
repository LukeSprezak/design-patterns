<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\SingleTable;

use PDO;

/** One table for the whole hierarchy: subclass columns are nullable because they apply to one type only. */
final class SingleTableShipmentSchema
{
    public static function create(PDO $connection): void
    {
        $connection->exec(<<<SQL
            CREATE TABLE shipments (
                tracking_number        TEXT PRIMARY KEY,
                type                   TEXT NOT NULL,
                sender_name            TEXT NOT NULL,
                weight_in_grams        INTEGER,
                pallet_count           INTEGER,
                exchanges_euro_pallets INTEGER
            );
            SQL);
    }
}
