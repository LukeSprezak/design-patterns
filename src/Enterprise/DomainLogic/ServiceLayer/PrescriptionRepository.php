<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\ServiceLayer;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;

interface PrescriptionRepository
{
    public function get(int $prescriptionId): Prescription;

    public function save(Prescription $prescription): void;
}
