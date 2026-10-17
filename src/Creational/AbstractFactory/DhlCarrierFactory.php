<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final class DhlCarrierFactory implements CarrierFactory
{
    #[\Override]
    public function createTrackingNumberGenerator(): TrackingNumberGenerator
    {
        return new DhlTrackingNumberGenerator();
    }

    #[\Override]
    public function createShippingLabelGenerator(): ShippingLabelGenerator
    {
        return new DhlShippingLabelGenerator();
    }

    #[\Override]
    public function createShippingCostCalculator(): ShippingCostCalculator
    {
        return new DhlShippingCostCalculator();
    }
}
