<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Composite;

interface LabTest
{
    public function priceInCents(): int;

    public function turnaroundTimeInHours(): int;

    /** @return list<SampleType> */
    public function requiredSampleTypes(): array;
}
