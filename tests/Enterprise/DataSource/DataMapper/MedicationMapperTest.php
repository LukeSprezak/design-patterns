<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\DataMapper;

use DesignPatterns\Enterprise\DataSource\DataMapper\MedicationMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\RecordNotFoundException;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\RefundLevel;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MedicationMapper::class)]
#[CoversClass(RecordNotFoundException::class)]
final class MedicationMapperTest extends TestCase
{
    private PDO $connection;
    private MedicationMapper $medications;

    protected function setUp(): void
    {
        $this->connection = PharmacyFixture::connection();
        $this->medications = new MedicationMapper($this->connection);
    }

    #[Test]
    public function getBuildsDomainObjectFromRow(): void
    {
        $insulin = $this->medications->get('5909998887776');

        self::assertInstanceOf(Medication::class, $insulin);
        self::assertSame('Insulin Lantus', $insulin->name);
        self::assertSame(12000, $insulin->priceInCents);
        self::assertSame(3, $insulin->stock);
    }

    #[Test]
    public function getThrowsForUnknownEan(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('0000000000000');

        $this->medications->get('0000000000000');
    }

    #[Test]
    public function saveWritesChangedStockBackToRow(): void
    {
        $apap = $this->medications->get('5901234123457');
        $apap->dispense(4, RefundLevel::Full);

        $this->medications->save($apap);

        self::assertSame(6, (int) $this->connection->query("SELECT stock FROM medications WHERE ean = '5901234123457'")->fetchColumn());
    }
}
