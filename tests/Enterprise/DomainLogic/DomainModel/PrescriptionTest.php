<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\DomainModel;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\PrescriptionItem;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\RefundLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Prescription::class)]
#[CoversClass(Medication::class)]
final class PrescriptionTest extends TestCase
{
    private Medication $apap;
    private Medication $insulin;
    private Medication $amoxicillin;

    protected function setUp(): void
    {
        $this->apap = new Medication(ean: '5901234123457', name: 'Apap 500 mg', priceInCents: 749, stock: 10);
        $this->insulin = new Medication(ean: '5909998887776', name: 'Insulin Lantus', priceInCents: 12000, stock: 3);
        $this->amoxicillin = new Medication(ean: '5901112223330', name: 'Amoxicillin 500 mg', priceInCents: 2000, stock: 1);
    }

    #[Test]
    public function dispensingChargesPatientAccordingToRefundLevels(): void
    {
        $sale = $this->validPrescription()->dispense(new DateTimeImmutable('2026-10-18'));

        // Apap 2 x 749 (100%) + Insulin flat fee 320 (R) + Amoxicillin 50% of 2000
        self::assertSame(1498 + 320 + 1000, $sale->totalInCents);
        self::assertSame(1, $sale->prescriptionId);
        self::assertCount(3, $sale->lines);
    }

    #[Test]
    public function dispensingReducesStockAndMarksPrescriptionAsDispensed(): void
    {
        $prescription = $this->validPrescription();

        (void) $prescription->dispense(new DateTimeImmutable('2026-10-18'));

        self::assertSame(8, $this->apap->stock);
        self::assertSame(2, $this->insulin->stock);
        self::assertSame(0, $this->amoxicillin->stock);
        self::assertEquals(new DateTimeImmutable('2026-10-18'), $prescription->dispensedAt);
    }

    #[Test]
    public function rejectsExpiredPrescription(): void
    {
        $prescription = new Prescription(
            id: 2,
            validUntil: new DateTimeImmutable('2026-09-30'),
            items: [new PrescriptionItem($this->apap, quantity: 1, refundLevel: RefundLevel::Full)],
        );

        $this->expectException(DispensingException::class);
        $this->expectExceptionMessage('expired on 2026-09-30');

        (void) $prescription->dispense(new DateTimeImmutable('2026-10-18'));
    }

    #[Test]
    public function rejectsAlreadyDispensedPrescription(): void
    {
        $prescription = $this->validPrescription();
        (void) $prescription->dispense(new DateTimeImmutable('2026-10-01'));

        $this->expectException(DispensingException::class);
        $this->expectExceptionMessage('already dispensed');

        (void) $prescription->dispense(new DateTimeImmutable('2026-10-18'));
    }

    #[Test]
    public function leavesStockUntouchedWhenAnyMedicationIsOutOfStock(): void
    {
        $outOfStockAmoxicillin = new Medication(ean: '5901112223330', name: 'Amoxicillin 500 mg', priceInCents: 2000, stock: 0);
        $prescription = new Prescription(
            id: 1,
            validUntil: new DateTimeImmutable('2026-11-15'),
            items: [
                new PrescriptionItem($this->apap, quantity: 2, refundLevel: RefundLevel::Full),
                new PrescriptionItem($this->insulin, quantity: 1, refundLevel: RefundLevel::FlatFee),
                new PrescriptionItem($outOfStockAmoxicillin, quantity: 1, refundLevel: RefundLevel::FiftyPercent),
            ],
        );

        try {
            (void) $prescription->dispense(new DateTimeImmutable('2026-10-18'));
            self::fail('Expected DispensingException');
        } catch (DispensingException $dispensingException) {
            self::assertStringContainsString('Amoxicillin 500 mg', $dispensingException->getMessage());
        }

        self::assertSame(10, $this->apap->stock);
        self::assertSame(3, $this->insulin->stock);
        self::assertNull($prescription->dispensedAt);
    }

    private function validPrescription(): Prescription
    {
        return new Prescription(
            id: 1,
            validUntil: new DateTimeImmutable('2026-11-15'),
            items: [
                new PrescriptionItem($this->apap, quantity: 2, refundLevel: RefundLevel::Full),
                new PrescriptionItem($this->insulin, quantity: 1, refundLevel: RefundLevel::FlatFee),
                new PrescriptionItem($this->amoxicillin, quantity: 1, refundLevel: RefundLevel::FiftyPercent),
            ],
        );
    }
}
