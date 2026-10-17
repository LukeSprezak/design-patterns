<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

enum InPostLockerSize: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';

    public static function fromParcel(Parcel $parcel): self
    {
        $dimensions = [$parcel->lengthInCentimeters, $parcel->widthInCentimeters, $parcel->heightInCentimeters];
        $smallestDimension = min($dimensions);

        return match (true) {
            $smallestDimension <= 8 => self::A,
            $smallestDimension <= 19 => self::B,
            default => self::C,
        };
    }
}
