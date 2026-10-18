<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\RefundLevel;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\SaleLine;

final class Medication
{
    public function __construct(
        public readonly string $ean,
        public readonly string $name,
        public readonly int $priceInCents,
        public private(set) int $stock,
    ) {
    }

    public function ensureInStock(int $quantity): void
    {
        if ($this->stock < $quantity) {
            throw DispensingException::insufficientStock($this, $quantity);
        }
    }

    public function dispense(int $quantity, RefundLevel $refundLevel): SaleLine
    {
        $this->ensureInStock($quantity);
        $this->stock -= $quantity;

        return new SaleLine($this, $quantity, $refundLevel->patientPriceInCents($this->priceInCents));
    }
}
