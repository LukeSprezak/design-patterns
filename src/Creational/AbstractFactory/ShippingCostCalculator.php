<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

interface ShippingCostCalculator
{
    public function calculate(Parcel $parcel): int;
}
