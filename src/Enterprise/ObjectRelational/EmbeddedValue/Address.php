<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue;

final readonly class Address
{
    public function __construct(
        public string $street,
        public string $postalCode,
        public string $city,
        public string $countryCode,
    ) {
    }
}
