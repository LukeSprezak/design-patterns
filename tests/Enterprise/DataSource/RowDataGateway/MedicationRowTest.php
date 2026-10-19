<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\RowDataGateway;

use DesignPatterns\Enterprise\DataSource\RowDataGateway\MedicationFinder;
use DesignPatterns\Enterprise\DataSource\RowDataGateway\MedicationRow;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySchema;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MedicationRow::class)]
final class MedicationRowTest extends TestCase
{
    private PDO $connection;
    private MedicationFinder $finder;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        PharmacySchema::create($this->connection);
        $this->connection->exec(<<<SQL
            INSERT INTO medications (ean, name, price_in_cents, stock) VALUES
                ('5901234123457', 'Apap 500 mg', 749, 10);
            SQL);
        $this->finder = new MedicationFinder($this->connection);
    }

    #[Test]
    public function insertsItselfAsNewRow(): void
    {
        $ibuprofen = new MedicationRow($this->connection, ean: '5900000000017', name: 'Ibuprofen 200 mg', priceInCents: 1299, stock: 20);

        $ibuprofen->insert();

        self::assertSame(20, $this->finder->find('5900000000017')?->stock);
    }

    #[Test]
    public function updatesChangedFieldsOfItsRow(): void
    {
        $apap = $this->finder->find('5901234123457');
        $apap->stock = 7;
        $apap->priceInCents = 799;

        $apap->update();

        $reloaded = $this->finder->find('5901234123457');
        self::assertSame(7, $reloaded->stock);
        self::assertSame(799, $reloaded->priceInCents);
    }

    #[Test]
    public function deletesItsRow(): void
    {
        $this->finder->find('5901234123457')->delete();

        self::assertNull($this->finder->find('5901234123457'));
    }
}
