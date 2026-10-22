<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\AssociationTableMapping;

use PDO;

/** route_shipments is the association table: it has no object of its own, only two foreign keys. */
final class DeliverySchema
{
    public static function create(PDO $connection): void
    {
        $connection->exec(<<<SQL
            CREATE TABLE delivery_routes (
                id           INTEGER PRIMARY KEY,
                courier_name TEXT NOT NULL
            );
            CREATE TABLE shipments (
                tracking_number TEXT PRIMARY KEY,
                recipient_city  TEXT NOT NULL
            );
            CREATE TABLE route_shipments (
                route_id        INTEGER NOT NULL REFERENCES delivery_routes (id),
                tracking_number TEXT NOT NULL REFERENCES shipments (tracking_number),
                PRIMARY KEY (route_id, tracking_number)
            );
            SQL);
    }
}
