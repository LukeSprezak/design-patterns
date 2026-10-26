<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Composite;

use DesignPatterns\Structural\Composite\LabTest;
use DesignPatterns\Structural\Composite\LabTestPackage;
use DesignPatterns\Structural\Composite\SampleType;
use DesignPatterns\Structural\Composite\SingleLabTest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LabTestPackage::class)]
#[CoversClass(SingleLabTest::class)]
final class LabTestPackageTest extends TestCase
{
    #[Test]
    public function priceAppliesDiscountOfEveryNestedPackage(): void
    {
        self::assertSame(7412, self::cardiologyPackage()->priceInCents());
    }

    #[Test]
    public function resultsAreReadyWhenSlowestTestIsDone(): void
    {
        self::assertSame(24, self::cardiologyPackage()->turnaroundTimeInHours());
    }

    #[Test]
    public function eachSampleTypeIsCollectedOnlyOnce(): void
    {
        self::assertSame([SampleType::Serum, SampleType::FluoridePlasma], self::cardiologyPackage()->requiredSampleTypes());
    }

    private static function cardiologyPackage(): LabTest
    {
        $lipidPanel = new LabTestPackage(discountPercent: 20, labTests: [
            new SingleLabTest(priceInCents: 1200, turnaroundTimeInHours: 4, sampleType: SampleType::Serum),
            new SingleLabTest(priceInCents: 1500, turnaroundTimeInHours: 4, sampleType: SampleType::Serum),
            new SingleLabTest(priceInCents: 1500, turnaroundTimeInHours: 8, sampleType: SampleType::Serum),
            new SingleLabTest(priceInCents: 1200, turnaroundTimeInHours: 4, sampleType: SampleType::Serum),
        ]);
        $glucose = new SingleLabTest(priceInCents: 900, turnaroundTimeInHours: 4, sampleType: SampleType::FluoridePlasma);
        $highSensitivityCReactiveProtein = new SingleLabTest(priceInCents: 3500, turnaroundTimeInHours: 24, sampleType: SampleType::Serum);

        return new LabTestPackage(discountPercent: 15, labTests: [$lipidPanel, $glucose, $highSensitivityCReactiveProtein]);
    }
}
