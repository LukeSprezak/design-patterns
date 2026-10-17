<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Builder;

final readonly class Prescription
{
    /** @param list<Medication> $medications */
    public function __construct(
        public Patient $patient,
        public Prescriber $prescriber,
        public array $medications,
        public int $validityInDays,
        public ?string $controlledSubstancePermitNumber,
    ) {
    }
}
