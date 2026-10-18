<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\MedicationRepository;
use OutOfBoundsException;

final class InMemoryMedicationRepository implements MedicationRepository
{
    /** @var array<string, Medication> */
    private array $medicationsByEan = [];

    #[\Override]
    public function get(string $ean): Medication
    {
        return $this->medicationsByEan[$ean] ?? throw new OutOfBoundsException("Medication {$ean} not found");
    }

    #[\Override]
    public function save(Medication $medication): void
    {
        $this->medicationsByEan[$medication->ean] = $medication;
    }
}
