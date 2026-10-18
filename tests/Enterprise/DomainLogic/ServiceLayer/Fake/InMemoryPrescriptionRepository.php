<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\PrescriptionRepository;
use OutOfBoundsException;

final class InMemoryPrescriptionRepository implements PrescriptionRepository
{
    /** @var array<int, Prescription> */
    private array $prescriptionsById = [];

    #[\Override]
    public function get(int $prescriptionId): Prescription
    {
        return $this->prescriptionsById[$prescriptionId]
            ?? throw new OutOfBoundsException("Prescription {$prescriptionId} not found");
    }

    #[\Override]
    public function save(Prescription $prescription): void
    {
        $this->prescriptionsById[$prescription->id] = $prescription;
    }
}
