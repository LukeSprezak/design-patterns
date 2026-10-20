<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\UnitOfWork\Fake;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\MedicationRepository;
use OutOfBoundsException;

final class SpyMedicationRepository implements MedicationRepository
{
    /** @var list<string> every EAN passed to save(), in order */
    public private(set) array $savedEans = [];

    /** @var array<string, Medication> */
    private array $medicationsByEan = [];

    public function __construct(Medication ...$medications)
    {
        foreach ($medications as $medication) {
            $this->medicationsByEan[$medication->ean] = $medication;
        }
    }

    #[\Override]
    public function get(string $ean): Medication
    {
        return $this->medicationsByEan[$ean] ?? throw new OutOfBoundsException("Medication {$ean} not found");
    }

    #[\Override]
    public function save(Medication $medication): void
    {
        $this->savedEans[] = $medication->ean;
    }
}
