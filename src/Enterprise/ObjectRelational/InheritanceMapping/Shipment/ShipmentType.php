<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\InheritanceMapping\Shipment;

/** Discriminator stored in the shipments.type column so a mapper knows which subclass a row represents. */
enum ShipmentType: string
{
    case Parcel = 'parcel';
    case Pallet = 'pallet';

    public static function of(Shipment $shipment): self
    {
        return match (true) {
            $shipment instanceof ParcelShipment => self::Parcel,
            $shipment instanceof PalletShipment => self::Pallet,
        };
    }
}
