<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\AssociationTableMapping;

final readonly class Shipment
{
    public function __construct(
        public string $trackingNumber,
        public string $recipientCity,
    ) {
    }
}
