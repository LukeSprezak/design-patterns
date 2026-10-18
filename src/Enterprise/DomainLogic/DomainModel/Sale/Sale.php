<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\RefundLevel;

final class Sale
{
    /** @param list<SaleLine> $lines */
    public function __construct(
        public readonly ?int $prescriptionId,
        public readonly array $lines,
        public readonly DateTimeImmutable $soldAt,
    ) {
    }

    public int $totalInCents {
        get => array_sum(array_map(static fn(SaleLine $line): int => $line->totalInCents(), $this->lines));
    }

    #[\NoDiscard('the sale must be recorded and its total handed to the cash register')]
    public static function overTheCounter(Medication $medication, int $quantity, DateTimeImmutable $soldAt): self
    {
        return new self(null, [$medication->dispense($quantity, RefundLevel::Full)], $soldAt);
    }
}
