<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory\InPost;

use DesignPatterns\Creational\AbstractFactory\Parcel;
use DomainException;

final class ParcelDoesNotFitLockerException extends DomainException
{
    public function __construct(Parcel $parcel)
    {
        parent::__construct(sprintf(
            'Parcel %d x %d x %d cm does not fit any InPost locker (max 41 x 38 x 64 cm)',
            $parcel->lengthInCentimeters,
            $parcel->widthInCentimeters,
            $parcel->heightInCentimeters,
        ));
    }
}
