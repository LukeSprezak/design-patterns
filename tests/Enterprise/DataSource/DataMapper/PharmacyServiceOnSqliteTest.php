<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\DataMapper;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DataSource\DataMapper\MedicationMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\PdoTransactionManager;
use DesignPatterns\Enterprise\DataSource\DataMapper\PrescriptionMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\SaleMapper;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\PharmacyService;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** The Service Layer's use cases end to end: Domain Model objects, mapped by Data Mappers, inside one PDO transaction. */
#[CoversClass(PharmacyService::class)]
final class PharmacyServiceOnSqliteTest extends TestCase
{
    private PDO $connection;
    private PharmacyService $pharmacyService;

    protected function setUp(): void
    {
        $this->connection = PharmacyFixture::connection();
        $this->pharmacyService = new PharmacyService(
            new PrescriptionMapper($this->connection),
            new MedicationMapper($this->connection),
            new SaleMapper($this->connection),
            new PdoTransactionManager($this->connection),
        );
    }

    #[Test]
    public function dispensingPersistsStockPrescriptionAndSaleInOneTransaction(): void
    {
        $amountToPayInCents = $this->pharmacyService->dispensePrescription(1, new DateTimeImmutable('2026-10-18'));

        self::assertSame(1498 + 320 + 1000, $amountToPayInCents);
        self::assertSame(['Amoxicillin 500 mg' => 0, 'Apap 500 mg' => 8, 'Insulin Lantus' => 2], $this->stockLevels());
        self::assertNotNull($this->connection->query('SELECT dispensed_at FROM prescriptions WHERE id = 1')->fetchColumn());
        self::assertSame(3, (int) $this->connection->query('SELECT COUNT(*) FROM sale_items')->fetchColumn());
    }

    #[Test]
    public function expiredPrescriptionLeavesDatabaseUntouched(): void
    {
        try {
            (void) $this->pharmacyService->dispensePrescription(2, new DateTimeImmutable('2026-10-18'));
            self::fail('Expected DispensingException');
        } catch (DispensingException) {
        }

        self::assertSame(['Amoxicillin 500 mg' => 1, 'Apap 500 mg' => 10, 'Insulin Lantus' => 3], $this->stockLevels());
        self::assertSame(0, (int) $this->connection->query('SELECT COUNT(*) FROM sales')->fetchColumn());
    }

    #[Test]
    public function overTheCounterSaleIsRecordedWithoutPrescription(): void
    {
        $amountToPayInCents = $this->pharmacyService->sellOverTheCounter('5901234123457', 3, new DateTimeImmutable('2026-10-18'));

        self::assertSame(3 * 749, $amountToPayInCents);
        self::assertSame(7, (int) $this->connection->query("SELECT stock FROM medications WHERE ean = '5901234123457'")->fetchColumn());
        self::assertNull($this->connection->query('SELECT prescription_id FROM sales')->fetchColumn());
    }

    /** @return array<string, int> medication name => stock */
    private function stockLevels(): array
    {
        return $this->connection->query('SELECT name, stock FROM medications ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
