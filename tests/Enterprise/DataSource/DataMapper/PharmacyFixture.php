<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\DataMapper;

use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySchema;
use PDO;

final class PharmacyFixture
{
    public static function connection(): PDO
    {
        $connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        PharmacySchema::create($connection);
        $connection->exec(<<<SQL
            INSERT INTO medications (ean, name, price_in_cents, stock) VALUES
                ('5901234123457', 'Apap 500 mg', 749, 10),
                ('5909998887776', 'Insulin Lantus', 12000, 3),
                ('5901112223330', 'Amoxicillin 500 mg', 2000, 1);
            INSERT INTO prescriptions (id, valid_until, dispensed_at) VALUES
                (1, '2026-11-15', NULL),
                (2, '2026-09-30', NULL);
            INSERT INTO prescription_items (prescription_id, ean, quantity, refund_level) VALUES
                (1, '5901234123457', 2, '100'),
                (1, '5909998887776', 1, 'R'),
                (1, '5901112223330', 1, '50'),
                (2, '5901234123457', 1, '100');
            SQL);

        return $connection;
    }
}
