<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\SaleLine;

final class Prescription
{
    /** @param list<PrescriptionItem> $items */
    public function __construct(
        public readonly int $id,
        public readonly DateTimeImmutable $validUntil,
        private readonly array $items,
        public private(set) ?DateTimeImmutable $dispensedAt = null,
    ) {
    }

    #[\NoDiscard('the sale must be recorded and its total handed to the cash register')]
    public function dispense(DateTimeImmutable $today): Sale
    {
        if ($this->dispensedAt !== null) {
            throw DispensingException::alreadyDispensed($this, $this->dispensedAt);
        }
        if ($this->validUntil->format('Y-m-d') < $today->format('Y-m-d')) {
            throw DispensingException::expired($this);
        }

        // All-or-nothing: verify every item before touching any stock level.
        foreach ($this->items as $item) {
            $item->medication->ensureInStock($item->quantity);
        }

        $saleLines = array_map(
            static fn(PrescriptionItem $item): SaleLine => $item->medication->dispense($item->quantity, $item->refundLevel),
            $this->items,
        );
        $this->dispensedAt = $today;

        return new Sale($this->id, $saleLines, $today);
    }
}
