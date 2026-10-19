<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\DataMapper;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DataSource\DataMapper\MedicationMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\SaleMapper;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SaleMapper::class)]
final class SaleMapperTest extends TestCase
{
    private PDO $connection;

    protected function setUp(): void
    {
        $this->connection = PharmacyFixture::connection();
    }

    #[Test]
    public function addInsertsSaleHeaderAndOneRowPerLine(): void
    {
        $apap = new MedicationMapper($this->connection)->get('5901234123457');
        $sale = Sale::overTheCounter($apap, 3, new DateTimeImmutable('2026-10-18 12:00:00'));

        new SaleMapper($this->connection)->add($sale);

        self::assertSame(
            ['prescription_id' => null, 'total_in_cents' => 3 * 749, 'sold_at' => '2026-10-18 12:00:00'],
            $this->connection->query('SELECT prescription_id, total_in_cents, sold_at FROM sales')->fetch(PDO::FETCH_ASSOC),
        );
        self::assertSame(
            [['sale_id' => 1, 'ean' => '5901234123457', 'quantity' => 3, 'price_in_cents' => 749]],
            $this->connection->query('SELECT sale_id, ean, quantity, price_in_cents FROM sale_items')->fetchAll(PDO::FETCH_ASSOC),
        );
    }
}
