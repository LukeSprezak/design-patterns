<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\TableModule;

use DesignPatterns\Enterprise\DomainLogic\TableModule\DispatchOutcome;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DispatchOutcome::class)]
final class DispatchOutcomeTest extends TestCase
{
    #[Test]
    public function onlyCancelledDispatchIsNotBillable(): void
    {
        self::assertTrue(DispatchOutcome::Transported->isBillable());
        self::assertTrue(DispatchOutcome::TreatedOnSite->isBillable());
        self::assertFalse(DispatchOutcome::Cancelled->isBillable());
    }
}
