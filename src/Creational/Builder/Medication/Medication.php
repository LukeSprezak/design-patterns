<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Builder\Medication;

final readonly class Medication
{
    public function __construct(
        public string $name,
        public string $dosage,
        public int $packageCount,
        public MedicationCategory $category,
    ) {
    }
}
