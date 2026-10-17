<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

interface ShippingLabelGenerator
{
    public function generate(Parcel $parcel, string $trackingNumber): string;
}
