<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final class InPostTrackingNumberGenerator implements TrackingNumberGenerator
{
    #[\Override]
    public function generate(): string
    {
        return RandomDigits::ofLength(24);
    }
}
