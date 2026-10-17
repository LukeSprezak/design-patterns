<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

final class DhlTrackingNumberGenerator implements TrackingNumberGenerator
{
    #[\Override]
    public function generate(): string
    {
        return 'JD' . RandomDigits::ofLength(18);
    }
}
