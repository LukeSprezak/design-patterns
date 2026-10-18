<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TransactionScript;

use PDO;

final class PharmacySchema
{
    public static function create(PDO $connection): void
    {
        $connection->exec(<<<SQL
            CREATE TABLE medications (
                ean            TEXT PRIMARY KEY,
                name           TEXT NOT NULL,
                price_in_cents INTEGER NOT NULL,
                stock          INTEGER NOT NULL
            );
            CREATE TABLE prescriptions (
                id           INTEGER PRIMARY KEY,
                valid_until  TEXT NOT NULL,
                dispensed_at TEXT NULL
            );
            CREATE TABLE prescription_items (
                prescription_id INTEGER NOT NULL REFERENCES prescriptions (id),
                ean             TEXT NOT NULL REFERENCES medications (ean),
                quantity        INTEGER NOT NULL,
                refund_level    TEXT NOT NULL
            );
            CREATE TABLE sales (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                prescription_id INTEGER NULL REFERENCES prescriptions (id),
                total_in_cents  INTEGER NOT NULL,
                sold_at         TEXT NOT NULL
            );
            CREATE TABLE sale_items (
                sale_id        INTEGER NOT NULL REFERENCES sales (id),
                ean            TEXT NOT NULL REFERENCES medications (ean),
                quantity       INTEGER NOT NULL,
                price_in_cents INTEGER NOT NULL
            );
            SQL);
    }
}
