<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\ServiceLayer;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;

interface MedicationRepository
{
    public function get(string $ean): Medication;

    public function save(Medication $medication): void;
}
