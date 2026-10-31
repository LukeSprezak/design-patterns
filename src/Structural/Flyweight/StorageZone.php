<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Flyweight;

enum StorageZone: string
{
    case Cold = 'COLD';
    case Ambient = 'AMBIENT';

    public function minimumTemperatureInCelsius(): int
    {
        return match ($this) {
            self::Cold => 2,
            self::Ambient => 15,
        };
    }

    public function maximumTemperatureInCelsius(): int
    {
        return match ($this) {
            self::Cold => 8,
            self::Ambient => 25,
        };
    }
}
