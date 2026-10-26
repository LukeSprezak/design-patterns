<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Composite;

final readonly class LabTestPackage implements LabTest
{
    /** @param list<LabTest> $labTests */
    public function __construct(
        private int $discountPercent,
        private array $labTests,
    ) {
    }

    #[\Override]
    public function priceInCents(): int
    {
        $priceOfLabTestsInCents = array_sum(array_map(static fn (LabTest $labTest): int => $labTest->priceInCents(), $this->labTests));

        return intdiv($priceOfLabTestsInCents * (100 - $this->discountPercent), 100);
    }

    #[\Override]
    public function turnaroundTimeInHours(): int
    {
        return max(array_map(static fn (LabTest $labTest): int => $labTest->turnaroundTimeInHours(), $this->labTests));
    }

    #[\Override]
    public function requiredSampleTypes(): array
    {
        $sampleTypes = array_merge(...array_map(static fn (LabTest $labTest): array => $labTest->requiredSampleTypes(), $this->labTests));

        // One tube of a given type serves every test in the package, so duplicates are dropped by case name.
        return array_values(array_column($sampleTypes, null, 'name'));
    }
}
