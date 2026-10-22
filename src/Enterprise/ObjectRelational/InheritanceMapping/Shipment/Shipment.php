<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment;

abstract readonly class Shipment
{
    public function __construct(
        public string $trackingNumber,
        public string $senderName,
    ) {
    }

    abstract public function priceInCents(): int;
}
