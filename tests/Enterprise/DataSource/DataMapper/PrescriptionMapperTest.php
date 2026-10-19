<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DataSource\DataMapper;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DataSource\DataMapper\PrescriptionMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\RecordNotFoundException;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrescriptionMapper::class)]
final class PrescriptionMapperTest extends TestCase
{
    private PDO $connection;
    private PrescriptionMapper $prescriptions;

    protected function setUp(): void
    {
        $this->connection = PharmacyFixture::connection();
        $this->prescriptions = new PrescriptionMapper($this->connection);
    }

    #[Test]
    public function getAssemblesPrescriptionWithItsItemsAndMedications(): void
    {
        $prescription = $this->prescriptions->get(1);

        $sale = $prescription->dispense(new DateTimeImmutable('2026-10-18'));

        self::assertSame('2026-11-15', $prescription->validUntil->format('Y-m-d'));
        self::assertSame(1498 + 320 + 1000, $sale->totalInCents);
        self::assertSame(['Apap 500 mg', 'Insulin Lantus', 'Amoxicillin 500 mg'], array_map(static fn ($line): string => $line->medication->name, $sale->lines));
    }

    #[Test]
    public function getThrowsForUnknownId(): void
    {
        $this->expectException(RecordNotFoundException::class);
        $this->expectExceptionMessage('99');

        $this->prescriptions->get(99);
    }

    #[Test]
    public function saveWritesDispensedAtBackToRow(): void
    {
        $prescription = $this->prescriptions->get(1);
        (void) $prescription->dispense(new DateTimeImmutable('2026-10-18 10:30:00'));

        $this->prescriptions->save($prescription);

        self::assertSame('2026-10-18 10:30:00', $this->connection->query('SELECT dispensed_at FROM prescriptions WHERE id = 1')->fetchColumn());
        self::assertNotNull($this->prescriptions->get(1)->dispensedAt);
    }
}
