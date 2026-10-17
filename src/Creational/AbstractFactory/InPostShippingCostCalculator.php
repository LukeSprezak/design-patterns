<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final class InPostShippingCostCalculator implements ShippingCostCalculator
{
    #[\Override]
    public function calculate(Parcel $parcel): int
    {
        return match (InPostLockerSize::fromParcel($parcel)) {
            InPostLockerSize::A => 1299,
            InPostLockerSize::B => 1499,
            InPostLockerSize::C => 1899,
        };
    }
}
