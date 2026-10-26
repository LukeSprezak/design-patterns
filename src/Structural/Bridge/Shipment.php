<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge;

final readonly class Shipment
{
    public function __construct(
        public string $trackingNumber,
        public Address $sender,
        public Address $recipient,
    ) {
    }
}
