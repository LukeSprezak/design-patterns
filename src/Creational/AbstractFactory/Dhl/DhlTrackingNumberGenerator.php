<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory\Dhl;

use DesignPatterns\Creational\AbstractFactory\TrackingNumberGenerator;
use Random\Randomizer;

final readonly class DhlTrackingNumberGenerator implements TrackingNumberGenerator
{
    public function __construct(
        private Randomizer $randomizer,
    ) {
    }

    #[\Override]
    public function generate(): string
    {
        return 'JD' . $this->randomizer->getBytesFromString('0123456789', 18);
    }
}
