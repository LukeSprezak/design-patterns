<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final readonly class Parcel
{
    public function __construct(
        public int $weightInGrams,
        public int $lengthInCentimeters,
        public int $widthInCentimeters,
        public int $heightInCentimeters,
        public string $recipientName,
        public string $deliveryAddress,
    ) {
    }
}
