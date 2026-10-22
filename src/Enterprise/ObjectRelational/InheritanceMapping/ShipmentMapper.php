<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping;

use DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment\Shipment;

interface ShipmentMapper
{
    public function insert(Shipment $shipment): void;

    public function find(string $trackingNumber): ?Shipment;

    /** @return list<Shipment> ordered by tracking number */
    public function findBySender(string $senderName): array;
}
