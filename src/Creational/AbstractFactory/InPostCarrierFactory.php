<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final class InPostCarrierFactory implements CarrierFactory
{
    #[\Override]
    public function createTrackingNumberGenerator(): TrackingNumberGenerator
    {
        return new InPostTrackingNumberGenerator();
    }

    #[\Override]
    public function createShippingLabelGenerator(): ShippingLabelGenerator
    {
        return new InPostShippingLabelGenerator();
    }

    #[\Override]
    public function createShippingCostCalculator(): ShippingCostCalculator
    {
        return new InPostShippingCostCalculator();
    }
}
