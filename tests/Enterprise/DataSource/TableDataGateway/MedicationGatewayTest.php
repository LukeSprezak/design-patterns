<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\TableDataGateway;

use DesignPatterns\Enterprise\DataSource\TableDataGateway\MedicationGateway;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySchema;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MedicationGateway::class)]
final class MedicationGatewayTest extends TestCase
{
    private MedicationGateway $medications;

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
        $this->medications = new MedicationGateway($connection);
    }

    #[Test]
    public function findReturnsRowByEanOrNull(): void
    {
        self::assertSame(
            ['ean' => '5909998887776', 'name' => 'Insulin Lantus', 'price_in_cents' => 12000, 'stock' => 3],
            $this->medications->find('5909998887776'),
        );
        self::assertNull($this->medications->find('0000000000000'));
    }

    #[Test]
    public function findBelowStockReturnsRowsOrderedByName(): void
    {
        $rows = $this->medications->findBelowStock(5);

        self::assertSame(['Amoxicillin 500 mg', 'Insulin Lantus'], array_column($rows, 'name'));
    }

    #[Test]
    public function insertsNewRow(): void
    {
        $this->medications->insert('5900000000017', 'Ibuprofen 200 mg', 1299, 20);

        self::assertSame(20, $this->medications->find('5900000000017')['stock']);
    }

    #[Test]
    public function updatesStockOfExistingRow(): void
    {
        $this->medications->updateStock('5901234123457', 7);

        self::assertSame(7, $this->medications->find('5901234123457')['stock']);
    }

    #[Test]
    public function deletesRow(): void
    {
        $this->medications->delete('5901112223330');

        self::assertNull($this->medications->find('5901112223330'));
        self::assertSame([], $this->medications->findBelowStock(2));
    }
}
