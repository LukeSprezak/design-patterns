<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\Builder\Prescription;

use DesignPatterns\Creational\Builder\InvalidPrescriptionException;
use DesignPatterns\Creational\Builder\Medication\Medication;
use DesignPatterns\Creational\Builder\Medication\MedicationCategory;
use DesignPatterns\Creational\Builder\Patient;
use DesignPatterns\Creational\Builder\Prescriber;
use DesignPatterns\Creational\Builder\Prescription\PrescriptionBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrescriptionBuilder::class)]
final class PrescriptionBuilderTest extends TestCase
{
    #[Test]
    public function buildsPrescriptionValidForThirtyDaysByDefault(): void
    {
        $prescription = self::completeBuilder()
            ->addMedication(self::standardMedication())
            ->build();

        self::assertSame('90010112345', $prescription->patient->pesel);
        self::assertSame('5425740', $prescription->prescriber->licenseNumber);
        self::assertCount(1, $prescription->medications);
        self::assertSame(30, $prescription->validityInDays);
        self::assertNull($prescription->controlledSubstancePermitNumber);
    }

    #[Test]
    public function limitsValidityToSevenDaysWhenAntibioticIsPrescribed(): void
    {
        $prescription = self::completeBuilder()
            ->addMedication(self::standardMedication())
            ->addMedication(self::antibiotic())
            ->build();

        self::assertSame(7, $prescription->validityInDays);
    }

    #[Test]
    public function acceptsExplicitValidityShorterThanAllowed(): void
    {
        $prescription = self::completeBuilder()
            ->addMedication(self::antibiotic())
            ->validForDays(5)
            ->build();

        self::assertSame(5, $prescription->validityInDays);
    }

    #[Test]
    public function rejectsValidityLongerThanAllowedForAntibiotic(): void
    {
        $this->expectException(InvalidPrescriptionException::class);
        $this->expectExceptionMessage('Validity of 14 days exceeds the 7 days allowed');

        (void) self::completeBuilder()
            ->addMedication(self::antibiotic())
            ->validForDays(14)
            ->build();
    }

    #[Test]
    public function rejectsPrescriptionWithoutMedications(): void
    {
        $this->expectException(InvalidPrescriptionException::class);
        $this->expectExceptionMessage('at least one medication');

        (void) self::completeBuilder()->build();
    }

    #[Test]
    public function rejectsMoreThanFiveMedications(): void
    {
        $this->expectException(InvalidPrescriptionException::class);
        $this->expectExceptionMessage('6 medications exceed the limit of 5');

        $prescriptionBuilder = self::completeBuilder();
        foreach (range(1, 6) as $medicationNumber) {
            $prescriptionBuilder->addMedication(self::standardMedication("Medication {$medicationNumber}"));
        }
        (void) $prescriptionBuilder->build();
    }

    #[Test]
    public function rejectsControlledSubstanceWithoutPermit(): void
    {
        $this->expectException(InvalidPrescriptionException::class);
        $this->expectExceptionMessage('Morphine');

        (void) self::completeBuilder()
            ->addMedication(self::controlledSubstance())
            ->build();
    }

    #[Test]
    public function acceptsControlledSubstanceWithPermit(): void
    {
        $prescription = self::completeBuilder()
            ->addMedication(self::controlledSubstance())
            ->withControlledSubstancePermit('RPW-2024-0042')
            ->build();

        self::assertSame('RPW-2024-0042', $prescription->controlledSubstancePermitNumber);
    }

    #[Test]
    public function rejectsPrescriptionWithoutPatient(): void
    {
        $this->expectException(InvalidPrescriptionException::class);
        $this->expectExceptionMessage('patient');

        (void) new PrescriptionBuilder()
            ->prescribedBy(self::prescriber())
            ->addMedication(self::standardMedication())
            ->build();
    }

    #[Test]
    public function buildsIndependentPrescriptionsFromTheSameBuilder(): void
    {
        $prescriptionBuilder = self::completeBuilder()->addMedication(self::standardMedication());

        $firstPrescription = $prescriptionBuilder->build();
        $secondPrescription = $prescriptionBuilder->build();

        self::assertEquals($firstPrescription, $secondPrescription);
        self::assertNotSame($firstPrescription, $secondPrescription);
    }

    private static function completeBuilder(): PrescriptionBuilder
    {
        return new PrescriptionBuilder()
            ->forPatient(new Patient(pesel: '90010112345', fullName: 'Jan Kowalski'))
            ->prescribedBy(self::prescriber());
    }

    private static function prescriber(): Prescriber
    {
        return new Prescriber(fullName: 'dr Anna Nowak', licenseNumber: '5425740');
    }

    private static function standardMedication(string $name = 'Ibuprofen 400 mg'): Medication
    {
        return new Medication(
            name: $name,
            dosage: '1 tablet every 8 hours',
            packageCount: 1,
            category: MedicationCategory::Standard,
        );
    }

    private static function antibiotic(): Medication
    {
        return new Medication(
            name: 'Amoxicillin 500 mg',
            dosage: '1 capsule every 8 hours for 7 days',
            packageCount: 1,
            category: MedicationCategory::Antibiotic,
        );
    }

    private static function controlledSubstance(): Medication
    {
        return new Medication(
            name: 'Morphine 10 mg',
            dosage: '1 tablet every 12 hours',
            packageCount: 1,
            category: MedicationCategory::Controlled,
        );
    }
}
