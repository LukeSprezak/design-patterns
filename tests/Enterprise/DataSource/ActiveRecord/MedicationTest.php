<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\ActiveRecord;

use DesignPatterns\Enterprise\DataSource\ActiveRecord\InsufficientStockException;
use DesignPatterns\Enterprise\DataSource\ActiveRecord\Medication;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySchema;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Medication::class)]
#[CoversClass(InsufficientStockException::class)]
final class MedicationTest extends TestCase
{
    private PDO $connection;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        PharmacySchema::create($this->connection);
        $this->connection->exec(<<<SQL
            INSERT INTO medications (ean, name, price_in_cents, stock) VALUES
                ('5901234123457', 'Apap 500 mg', 749, 10);
            SQL);
    }

    #[Test]
    public function findLoadsRecordByEanOrReturnsNull(): void
    {
        $apap = Medication::find($this->connection, '5901234123457');

        self::assertSame('Apap 500 mg', $apap->name);
        self::assertSame(10, $apap->stock);
        self::assertNull(Medication::find($this->connection, '0000000000000'));
    }

    #[Test]
    public function dispensingReducesStockReturnsAmountToPayAndSavePersistsIt(): void
    {
        $apap = Medication::find($this->connection, '5901234123457');

        $amountToPayInCents = $apap->dispense(3);
        $apap->save();

        self::assertSame(3 * 749, $amountToPayInCents);
        self::assertSame(7, Medication::find($this->connection, '5901234123457')->stock);
    }

    #[Test]
    public function dispensingMoreThanStockIsRejectedAndLeavesStockUntouched(): void
    {
        $apap = Medication::find($this->connection, '5901234123457');

        try {
            (void) $apap->dispense(11);
            self::fail('Expected InsufficientStockException');
        } catch (InsufficientStockException $insufficientStock) {
            self::assertStringContainsString('Apap 500 mg', $insufficientStock->getMessage());
        }

        self::assertSame(10, $apap->stock);
    }

    #[Test]
    public function saveInsertsNewRecordAndRestockingUpdatesIt(): void
    {
        $ibuprofen = new Medication($this->connection, ean: '5900000000017', name: 'Ibuprofen 200 mg', priceInCents: 1299, stock: 0);
        $ibuprofen->save();

        $ibuprofen->restock(20);
        $ibuprofen->save();

        self::assertSame(20, Medication::find($this->connection, '5900000000017')->stock);
        self::assertSame(2, (int) $this->connection->query('SELECT COUNT(*) FROM medications')->fetchColumn());
    }
}
