<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\Builder;

final readonly class Patient
{
    public function __construct(
        public string $pesel,
        public string $fullName,
    ) {
    }
}
