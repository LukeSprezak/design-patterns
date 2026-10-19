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

#[CoversClass(MedicationFinder::class)]
final class MedicationFinderTest extends TestCase
{
    private MedicationFinder $finder;

    protected function setUp(): void
    {
        $connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        PharmacySchema::create($connection);
        $connection->exec(<<<SQL
            INSERT INTO medications (ean, name, price_in_cents, stock) VALUES
                ('5901234123457', 'Apap 500 mg', 749, 10),
                ('5909998887776', 'Insulin Lantus', 12000, 3),
                ('5901112223330', 'Amoxicillin 500 mg', 2000, 1);
            SQL);
        $this->finder = new MedicationFinder($connection);
    }

    #[Test]
    public function findReturnsRowObjectByEanOrNull(): void
    {
        $insulin = $this->finder->find('5909998887776');

        self::assertInstanceOf(MedicationRow::class, $insulin);
        self::assertSame('Insulin Lantus', $insulin->name);
        self::assertSame(12000, $insulin->priceInCents);
        self::assertSame(3, $insulin->stock);
        self::assertNull($this->finder->find('0000000000000'));
    }

    #[Test]
    public function findBelowStockReturnsRowObjectsOrderedByName(): void
    {
        $rows = $this->finder->findBelowStock(5);

        self::assertSame(
            ['Amoxicillin 500 mg', 'Insulin Lantus'],
            array_map(static fn (MedicationRow $row): string => $row->name, $rows),
        );
    }
}
