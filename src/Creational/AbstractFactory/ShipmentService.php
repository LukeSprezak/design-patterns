<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final readonly class ShipmentService
{
    public function __construct(
        private CarrierFactory $carrierFactory,
    ) {
    }

    #[\NoDiscard('the shipment carries the tracking number and label that must be stored and handed to the customer')]
    public function dispatch(Parcel $parcel): Shipment
    {
        $trackingNumber = $this->carrierFactory->createTrackingNumberGenerator()->generate();

        return new Shipment(
            trackingNumber: $trackingNumber,
            label: $this->carrierFactory->createShippingLabelGenerator()->generate($parcel, $trackingNumber),
            costInCents: $this->carrierFactory->createShippingCostCalculator()->calculate($parcel),
        );
    }
}
