<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory\Dhl;

use DesignPatterns\Creational\AbstractFactory\CarrierFactory;
use DesignPatterns\Creational\AbstractFactory\ShippingCostCalculator;
use DesignPatterns\Creational\AbstractFactory\ShippingLabelGenerator;
use DesignPatterns\Creational\AbstractFactory\TrackingNumberGenerator;
use Random\Randomizer;

final readonly class DhlCarrierFactory implements CarrierFactory
{
    public function __construct(
        private Randomizer $randomizer = new Randomizer(),
    ) {
    }

    #[\Override]
    public function createTrackingNumberGenerator(): TrackingNumberGenerator
    {
        return new DhlTrackingNumberGenerator($this->randomizer);
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
