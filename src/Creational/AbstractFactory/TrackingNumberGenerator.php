<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\AbstractFactory;

interface TrackingNumberGenerator
{
    public function generate(): string;
}
