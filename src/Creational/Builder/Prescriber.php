<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Builder;

final readonly class Prescriber
{
    public function __construct(
        public string $fullName,
        public string $licenseNumber,
    ) {
    }
}
