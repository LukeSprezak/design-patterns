<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Builder;

final class PrescriptionBuilder
{
    private const int MAX_MEDICATIONS = 5;
    private const int DEFAULT_VALIDITY_IN_DAYS = 30;
    private const int ANTIBIOTIC_VALIDITY_IN_DAYS = 7;

    private ?Patient $patient = null;
    private ?Prescriber $prescriber = null;
    /** @var list<Medication> */
    private array $medications = [];
    private ?int $requestedValidityInDays = null;
    private ?string $controlledSubstancePermitNumber = null;

    public function forPatient(Patient $patient): self
    {
        $this->patient = $patient;

        return $this;
    }

    public function prescribedBy(Prescriber $prescriber): self
    {
        $this->prescriber = $prescriber;

        return $this;
    }

    public function addMedication(Medication $medication): self
    {
        $this->medications[] = $medication;

        return $this;
    }

    public function withControlledSubstancePermit(string $permitNumber): self
    {
        $this->controlledSubstancePermitNumber = $permitNumber;

        return $this;
    }

    public function validForDays(int $days): self
    {
        $this->requestedValidityInDays = $days;

        return $this;
    }

    #[\NoDiscard('the built prescription is the only result of the builder')]
    public function build(): Prescription
    {
        $patient = $this->patient ?? throw InvalidPrescriptionException::missing('patient');
        $prescriber = $this->prescriber ?? throw InvalidPrescriptionException::missing('prescriber');

        $medicationCount = count($this->medications);
        if ($medicationCount === 0) {
            throw InvalidPrescriptionException::noMedications();
        }
        if ($medicationCount > self::MAX_MEDICATIONS) {
            throw InvalidPrescriptionException::tooManyMedications($medicationCount, self::MAX_MEDICATIONS);
        }

        $controlledSubstance = array_find($this->medications, self::isInCategory(MedicationCategory::Controlled));
        if ($controlledSubstance !== null && $this->controlledSubstancePermitNumber === null) {
            throw InvalidPrescriptionException::controlledSubstanceWithoutPermit($controlledSubstance);
        }

        $allowedValidityInDays = array_any($this->medications, self::isInCategory(MedicationCategory::Antibiotic))
            ? self::ANTIBIOTIC_VALIDITY_IN_DAYS
            : self::DEFAULT_VALIDITY_IN_DAYS;
        $validityInDays = $this->requestedValidityInDays ?? $allowedValidityInDays;
        if ($validityInDays > $allowedValidityInDays) {
            throw InvalidPrescriptionException::validityTooLong($validityInDays, $allowedValidityInDays);
        }

        return new Prescription(
            patient: $patient,
            prescriber: $prescriber,
            medications: $this->medications,
            validityInDays: $validityInDays,
            controlledSubstancePermitNumber: $this->controlledSubstancePermitNumber,
        );
    }

    /** @return \Closure(Medication): bool */
    private static function isInCategory(MedicationCategory $category): \Closure
    {
        return static fn(Medication $medication): bool => $medication->category === $category;
    }
}
