<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final class DhlShippingLabelGenerator implements ShippingLabelGenerator
{
    #[\Override]
    public function generate(Parcel $parcel, string $trackingNumber): string
    {
        $weightInKilograms = number_format($parcel->weightInGrams / 1000, 2, '.', '');

        return <<<LABEL
        DHL Parcel
        AWB: {$trackingNumber}
        Weight: {$weightInKilograms} kg
        {$parcel->recipientName}
        {$parcel->deliveryAddress}
        LABEL;
    }
}
