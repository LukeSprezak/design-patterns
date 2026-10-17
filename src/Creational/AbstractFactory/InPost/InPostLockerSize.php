<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory\InPost;

use DesignPatterns\Creational\AbstractFactory\Parcel;

enum InPostLockerSize: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';

    public static function fromParcel(Parcel $parcel): self
    {
        return array_find(self::cases(), static fn(self $lockerSize): bool => $lockerSize->fits($parcel))
            ?? throw new ParcelDoesNotFitLockerException($parcel);
    }

    /** @return list<int> sorted ascending */
    public function dimensionsInCentimeters(): array
    {
        return match ($this) {
            self::A => [8, 38, 64],
            self::B => [19, 38, 64],
            self::C => [38, 41, 64],
        };
    }

    private function fits(Parcel $parcel): bool
    {
        $sortedParcelDimensions = [$parcel->lengthInCentimeters, $parcel->widthInCentimeters, $parcel->heightInCentimeters];
        sort($sortedParcelDimensions);

        return array_all(
            array_map(null, $sortedParcelDimensions, $this->dimensionsInCentimeters()),
            static fn(array $parcelAndLockerDimension): bool => $parcelAndLockerDimension[0] <= $parcelAndLockerDimension[1],
        );
    }
}
