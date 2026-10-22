<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\LazyLoad;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DataSource\DataMapper\MedicationMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\PrescriptionMapper;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySchema;
use DesignPatterns\Enterprise\ObjectRelational\LazyLoad\PrescriptionFinder;
use DesignPatterns\Tests\Enterprise\ObjectRelational\LazyLoad\Fake\CountingPdo;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PrescriptionFinder::class)]
final class PrescriptionFinderTest extends TestCase
{
    private CountingPdo $connection;
    private PrescriptionFinder $finder;
    private ReflectionClass $prescriptionClass;

    protected function setUp(): void
    {
        $this->connection = new CountingPdo('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        PharmacySchema::create($this->connection);
        $this->connection->exec(<<<SQL
            INSERT INTO medications (ean, name, price_in_cents, stock) VALUES
                ('5901234123457', 'Apap 500 mg', 749, 10),
                ('5909998887776', 'Insulin Lantus', 12000, 3);
            INSERT INTO prescriptions (id, valid_until, dispensed_at) VALUES
                (1, '2026-11-15', NULL),
                (2, '2026-09-30', NULL),
                (3, '2026-12-01', NULL);
            INSERT INTO prescription_items (prescription_id, ean, quantity, refund_level) VALUES
                (1, '5901234123457', 2, '100'),
                (1, '5909998887776', 1, 'R'),
                (2, '5901234123457', 1, '100'),
                (3, '5901234123457', 1, '100');
            SQL);
        $this->finder = new PrescriptionFinder(
            $this->connection,
            new PrescriptionMapper($this->connection, new MedicationMapper($this->connection)),
        );
        $this->prescriptionClass = new ReflectionClass(Prescription::class);
    }

    #[Test]
    public function listingRunsOneQueryAndExposesIdAndValidityWithoutLoadingTheRest(): void
    {
        $prescriptions = $this->finder->findValidOn(new DateTimeImmutable('2026-10-20'));

        self::assertSame([1, 3], array_map(static fn (Prescription $prescription): int => $prescription->id, $prescriptions));
        self::assertSame('2026-12-01', $prescriptions[1]->validUntil->format('Y-m-d'));
        self::assertSame(1, $this->connection->preparedStatements);
        self::assertTrue($this->prescriptionClass->isUninitializedLazyObject($prescriptions[0]));
    }

    #[Test]
    public function itemsAndMedicationsLoadOnFirstUseOfTheRestOfTheObject(): void
    {
        $prescription = $this->finder->findValidOn(new DateTimeImmutable('2026-10-20'))[0];

        $sale = $prescription->dispense(new DateTimeImmutable('2026-10-20'));

        self::assertSame(2 * 749 + 320, $sale->totalInCents);
        self::assertFalse($this->prescriptionClass->isUninitializedLazyObject($prescription));
        // 1 list + 1 prescription header + 1 items + 2 medications
        self::assertSame(5, $this->connection->preparedStatements);
    }

    #[Test]
    public function initializedObjectIsNotLoadedAgain(): void
    {
        $prescription = $this->finder->findValidOn(new DateTimeImmutable('2026-10-20'))[0];
        (void) $prescription->dispense(new DateTimeImmutable('2026-10-20'));
        $queriesAfterFirstUse = $this->connection->preparedStatements;

        self::assertNotNull($prescription->dispensedAt);
        try {
            (void) $prescription->dispense(new DateTimeImmutable('2026-10-20'));
            self::fail('Expected DispensingException');
        } catch (DispensingException) {
        }

        self::assertSame($queriesAfterFirstUse, $this->connection->preparedStatements);
    }
}
