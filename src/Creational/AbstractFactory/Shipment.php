<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final readonly class Shipment
{
    public function __construct(
        public string $trackingNumber,
        public string $label,
        public int $costInCents,
    ) {
    }
}
