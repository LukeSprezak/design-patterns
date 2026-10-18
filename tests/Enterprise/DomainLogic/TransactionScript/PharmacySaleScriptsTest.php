<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\TransactionScript;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySaleScripts;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySchema;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PharmacySaleScripts::class)]
#[CoversClass(PharmacySchema::class)]
final class PharmacySaleScriptsTest extends TestCase
{
    private PDO $connection;
    private PharmacySaleScripts $pharmacySaleScripts;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        PharmacySchema::create($this->connection);
        $this->connection->exec(<<<SQL
            INSERT INTO medications (ean, name, price_in_cents, stock) VALUES
                ('5901234123457', 'Apap 500 mg', 749, 10),
                ('5909998887776', 'Insulin Lantus', 12000, 3),
                ('5901112223330', 'Amoxicillin 500 mg', 2000, 1);
            INSERT INTO prescriptions (id, valid_until, dispensed_at) VALUES
                (1, '2026-11-15', NULL),
                (2, '2026-09-30', NULL),
                (3, '2026-11-15', '2026-10-01 10:00:00');
            INSERT INTO prescription_items (prescription_id, ean, quantity, refund_level) VALUES
                (1, '5901234123457', 2, '100'),
                (1, '5909998887776', 1, 'R'),
                (1, '5901112223330', 1, '50'),
                (2, '5901234123457', 1, '100'),
                (3, '5901234123457', 1, '100');
            SQL);
        $this->pharmacySaleScripts = new PharmacySaleScripts($this->connection);
    }

    #[Test]
    public function dispensingChargesPatientAccordingToRefundLevels(): void
    {
        $amountToPayInCents = $this->pharmacySaleScripts->dispensePrescription(1, new DateTimeImmutable('2026-10-18'));

        // Apap 2 x 749 (100%) + Insulin flat fee 320 (R) + Amoxicillin 50% of 2000
        self::assertSame(1498 + 320 + 1000, $amountToPayInCents);
    }

    #[Test]
    public function dispensingReducesStockAndMarksPrescriptionAsDispensed(): void
    {
        (void) $this->pharmacySaleScripts->dispensePrescription(1, new DateTimeImmutable('2026-10-18'));

        self::assertSame(['Amoxicillin 500 mg' => 0, 'Apap 500 mg' => 8, 'Insulin Lantus' => 2], $this->stockLevels());
        self::assertNotNull($this->connection->query('SELECT dispensed_at FROM prescriptions WHERE id = 1')->fetchColumn());
        self::assertSame(3, $this->countRows('sale_items'));
    }

    #[Test]
    public function rejectsExpiredPrescription(): void
    {
        $this->expectException(DispensingException::class);
        $this->expectExceptionMessage('expired on 2026-09-30');

        (void) $this->pharmacySaleScripts->dispensePrescription(2, new DateTimeImmutable('2026-10-18'));
    }

    #[Test]
    public function rejectsAlreadyDispensedPrescription(): void
    {
        $this->expectException(DispensingException::class);
        $this->expectExceptionMessage('already dispensed');

        (void) $this->pharmacySaleScripts->dispensePrescription(3, new DateTimeImmutable('2026-10-18'));
    }

    #[Test]
    public function rollsBackEverythingWhenAnyMedicationIsOutOfStock(): void
    {
        $this->connection->exec("UPDATE medications SET stock = 0 WHERE ean = '5901112223330'");

        try {
            (void) $this->pharmacySaleScripts->dispensePrescription(1, new DateTimeImmutable('2026-10-18'));
            self::fail('Expected DispensingException');
        } catch (DispensingException $dispensingException) {
            self::assertStringContainsString('Amoxicillin 500 mg', $dispensingException->getMessage());
        }

        self::assertSame(['Amoxicillin 500 mg' => 0, 'Apap 500 mg' => 10, 'Insulin Lantus' => 3], $this->stockLevels());
        self::assertSame(0, $this->countRows('sales'));
        self::assertNull($this->connection->query('SELECT dispensed_at FROM prescriptions WHERE id = 1')->fetchColumn());
    }

    #[Test]
    public function overTheCounterSaleChargesFullPriceAndReducesStock(): void
    {
        $amountToPayInCents = $this->pharmacySaleScripts->sellOverTheCounter('5901234123457', 3, new DateTimeImmutable('2026-10-18'));

        self::assertSame(3 * 749, $amountToPayInCents);
        self::assertSame(['Amoxicillin 500 mg' => 1, 'Apap 500 mg' => 7, 'Insulin Lantus' => 3], $this->stockLevels());
        self::assertSame(1, $this->countRows('sales'));
    }

    #[Test]
    public function overTheCounterSaleRejectsUnknownEan(): void
    {
        $this->expectException(DispensingException::class);
        $this->expectExceptionMessage('0000000000000');

        (void) $this->pharmacySaleScripts->sellOverTheCounter('0000000000000', 1, new DateTimeImmutable('2026-10-18'));
    }

    /** @return array<string, int> medication name => stock */
    private function stockLevels(): array
    {
        return $this->connection
            ->query('SELECT name, stock FROM medications ORDER BY name')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private function countRows(string $table): int
    {
        return (int) $this->connection->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }
}
