<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TableModule;

use PDO;

final class EmergencyDispatchSchema
{
    public static function create(PDO $connection): void
    {
        $connection->exec(<<<SQL
            CREATE TABLE tariffs (
                crew_type                  TEXT PRIMARY KEY,
                base_fee_in_cents          INTEGER NOT NULL,
                fee_per_kilometer_in_cents INTEGER NOT NULL
            );
            CREATE TABLE dispatches (
                id            INTEGER PRIMARY KEY,
                crew_type     TEXT NOT NULL REFERENCES tariffs (crew_type),
                kilometers    INTEGER NOT NULL,
                outcome       TEXT NOT NULL,
                dispatched_at TEXT NOT NULL
            );
            SQL);
    }
}
