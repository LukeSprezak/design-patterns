<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\AssociationTableMapping;

/** A courier's route for one shift; a shipment can be unloaded after a failed delivery and loaded onto a later route. */
final class DeliveryRoute
{
    /** @var array<string, Shipment> keyed by tracking number */
    public private(set) array $shipments = [];

    public function __construct(
        public readonly int $id,
        public readonly string $courierName,
        Shipment ...$shipments,
    ) {
        foreach ($shipments as $shipment) {
            $this->load($shipment);
        }
    }

    public function load(Shipment $shipment): void
    {
        $this->shipments[$shipment->trackingNumber] = $shipment;
    }

    public function unload(Shipment $shipment): void
    {
        unset($this->shipments[$shipment->trackingNumber]);
    }
}
