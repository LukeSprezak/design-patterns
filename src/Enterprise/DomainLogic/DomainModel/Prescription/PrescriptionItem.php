<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;

final readonly class PrescriptionItem
{
    public function __construct(
        public Medication $medication,
        public int $quantity,
        public RefundLevel $refundLevel,
    ) {
    }
}
