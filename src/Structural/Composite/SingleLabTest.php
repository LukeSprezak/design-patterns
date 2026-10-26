<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Composite;

final readonly class SingleLabTest implements LabTest
{
    public function __construct(
        private int $priceInCents,
        private int $turnaroundTimeInHours,
        private SampleType $sampleType,
    ) {
    }

    #[\Override]
    public function priceInCents(): int
    {
        return $this->priceInCents;
    }

    #[\Override]
    public function turnaroundTimeInHours(): int
    {
        return $this->turnaroundTimeInHours;
    }

    #[\Override]
    public function requiredSampleTypes(): array
    {
        return [$this->sampleType];
    }
}
