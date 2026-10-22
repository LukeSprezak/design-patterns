<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue;

use PDO;

final class InvoiceSchema
{
    public static function create(PDO $connection): void
    {
        $connection->exec(<<<SQL
            CREATE TABLE invoices (
                number                 TEXT PRIMARY KEY,
                recipient_street       TEXT NOT NULL,
                recipient_postal_code  TEXT NOT NULL,
                recipient_city         TEXT NOT NULL,
                recipient_country_code TEXT NOT NULL,
                net_amount_in_cents    INTEGER NOT NULL,
                vat_amount_in_cents    INTEGER NOT NULL,
                currency               TEXT NOT NULL
            );
            SQL);
    }
}
