<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;

final readonly class SaleLine
{
    public function __construct(
        public Medication $medication,
        public int $quantity,
        public int $pricePerPackageInCents,
    ) {
    }

    public function totalInCents(): int
    {
        return $this->pricePerPackageInCents * $this->quantity;
    }
}
