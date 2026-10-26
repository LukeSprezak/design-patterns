<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge;

final readonly class Address
{
    public function __construct(
        public string $name,
        public string $street,
        public string $postalCode,
        public string $city,
    ) {
    }
}
