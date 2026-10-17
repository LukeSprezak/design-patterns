<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory\Dhl;

use DesignPatterns\Creational\AbstractFactory\Parcel;
use DesignPatterns\Creational\AbstractFactory\ShippingCostCalculator;

final class DhlShippingCostCalculator implements ShippingCostCalculator
{
    #[\Override]
    public function calculate(Parcel $parcel): int
    {
        return match (true) {
            $parcel->weightInGrams <= 1000 => 1500,
            $parcel->weightInGrams <= 5000 => 1900,
            $parcel->weightInGrams <= 10000 => 2500,
            default => 3500,
        };
    }
}
