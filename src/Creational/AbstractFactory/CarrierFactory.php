<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

interface CarrierFactory
{
    public function createTrackingNumberGenerator(): TrackingNumberGenerator;

    public function createShippingLabelGenerator(): ShippingLabelGenerator;

    public function createShippingCostCalculator(): ShippingCostCalculator;
}
