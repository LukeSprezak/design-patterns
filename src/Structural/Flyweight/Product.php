<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Flyweight;

final readonly class Product
{
    public function __construct(
        public string $ean,
        public string $name,
        public string $manufacturer,
        public string $atcCode,
        public int $wholesalePriceInCents,
        public int $minimumStorageTemperatureInCelsius,
        public int $maximumStorageTemperatureInCelsius,
    ) {
    }

    public function valueInCents(int $quantity): int
    {
        return $this->wholesalePriceInCents * $quantity;
    }

    public function canBeStoredIn(StorageZone $storageZone): bool
    {
        return $storageZone->minimumTemperatureInCelsius() >= $this->minimumStorageTemperatureInCelsius
            && $storageZone->maximumTemperatureInCelsius() <= $this->maximumStorageTemperatureInCelsius;
    }
}
