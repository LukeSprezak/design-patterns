<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory\InPost;

use DesignPatterns\Creational\AbstractFactory\TrackingNumberGenerator;
use Random\Randomizer;

final readonly class InPostTrackingNumberGenerator implements TrackingNumberGenerator
{
    public function __construct(
        private Randomizer $randomizer,
    ) {
    }

    #[\Override]
    public function generate(): string
    {
        return $this->randomizer->getBytesFromString('0123456789', 24);
    }
}
