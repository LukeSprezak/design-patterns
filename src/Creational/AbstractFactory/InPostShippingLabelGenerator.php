<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final class InPostShippingLabelGenerator implements ShippingLabelGenerator
{
    #[\Override]
    public function generate(Parcel $parcel, string $trackingNumber): string
    {
        $lockerSize = InPostLockerSize::fromParcel($parcel)->value;

        return <<<LABEL
        InPost Paczkomat
        Tracking: {$trackingNumber}
        Size: {$lockerSize}
        {$parcel->recipientName}
        {$parcel->deliveryAddress}
        LABEL;
    }
}
