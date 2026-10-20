<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\UnitOfWork;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DataSource\DataMapper\PdoTransactionManager;
use DesignPatterns\Enterprise\DataSource\DataMapper\PrescriptionMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\SaleMapper;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySchema;
use DesignPatterns\Enterprise\ObjectRelational\IdentityMap\IdentityMap;
use DesignPatterns\Enterprise\ObjectRelational\IdentityMap\MedicationMapper;
use DesignPatterns\Enterprise\ObjectRelational\UnitOfWork\PharmacyService;
use DesignPatterns\Enterprise\ObjectRelational\UnitOfWork\UnitOfWork;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PharmacyService::class)]
final class PharmacyServiceTest extends TestCase
{
    private PDO $connection;
    private PharmacyService $pharmacyService;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        PharmacySchema::create($this->connection);
        $this->connection->exec(<<<SQL
            INSERT INTO medications (ean, name, price_in_cents, stock) VALUES
                ('5901234123457', 'Apap 500 mg', 749, 10),
                ('5909998887776', 'Insulin Lantus', 12000, 3);
            INSERT INTO prescriptions (id, valid_until, dispensed_at) VALUES
                (1, '2026-11-15', NULL),
                (2, '2026-09-30', NULL);
            INSERT INTO prescription_items (prescription_id, ean, quantity, refund_level) VALUES
                (1, '5909998887776', 1, 'R'),
                (1, '5909998887776', 1, '30'),
                (1, '5901234123457', 2, '100'),
                (2, '5901234123457', 1, '100');
            SQL);

        $medications = new MedicationMapper($this->connection, new IdentityMap());
        $prescriptions = new PrescriptionMapper($this->connection, $medications);
        $unitOfWork = new UnitOfWork($medications, $prescriptions, new SaleMapper($this->connection), new PdoTransactionManager($this->connection));
        $this->pharmacyService = new PharmacyService($prescriptions, $medications, $unitOfWork);
    }

    #[Test]
    public function dispensingCommitsStockPrescriptionAndSaleTogether(): void
    {
        $amountToPayInCents = $this->pharmacyService->dispensePrescription(1, new DateTimeImmutable('2026-10-19'));

        // insulin flat fee 320 + insulin 30% of 12000 + 2 x Apap 749
        self::assertSame(320 + 3600 + 1498, $amountToPayInCents);
        self::assertSame(['Apap 500 mg' => 8, 'Insulin Lantus' => 1], $this->stockLevels());
        self::assertNotNull($this->connection->query('SELECT dispensed_at FROM prescriptions WHERE id = 1')->fetchColumn());
        self::assertSame(3, (int) $this->connection->query('SELECT COUNT(*) FROM sale_items')->fetchColumn());
    }

    #[Test]
    public function rejectedPrescriptionWritesNothing(): void
    {
        try {
            (void) $this->pharmacyService->dispensePrescription(2, new DateTimeImmutable('2026-10-19'));
            self::fail('Expected DispensingException');
        } catch (DispensingException) {
        }

        self::assertSame(['Apap 500 mg' => 10, 'Insulin Lantus' => 3], $this->stockLevels());
        self::assertSame(0, (int) $this->connection->query('SELECT COUNT(*) FROM sales')->fetchColumn());
    }

    #[Test]
    public function overTheCounterSaleCommitsStockAndSale(): void
    {
        $amountToPayInCents = $this->pharmacyService->sellOverTheCounter('5901234123457', 3, new DateTimeImmutable('2026-10-19'));

        self::assertSame(3 * 749, $amountToPayInCents);
        self::assertSame(['Apap 500 mg' => 7, 'Insulin Lantus' => 3], $this->stockLevels());
        self::assertNull($this->connection->query('SELECT prescription_id FROM sales')->fetchColumn());
    }

    /** @return array<string, int> medication name => stock */
    private function stockLevels(): array
    {
        return $this->connection->query('SELECT name, stock FROM medications ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
