<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\TableModule;

use DesignPatterns\Enterprise\DomainLogic\TableModule\Dispatches;
use DesignPatterns\Enterprise\DomainLogic\TableModule\EmergencyDispatchSchema;
use DesignPatterns\Enterprise\DomainLogic\TableModule\Tariffs;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Dispatches::class)]
final class DispatchesTest extends TestCase
{
    private Dispatches $dispatches;

    protected function setUp(): void
    {
        $connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        EmergencyDispatchSchema::create($connection);
        $connection->exec(<<<SQL
            INSERT INTO tariffs (crew_type, base_fee_in_cents, fee_per_kilometer_in_cents) VALUES
                ('S', 30000, 450),
                ('P', 20000, 350);
            INSERT INTO dispatches (id, crew_type, kilometers, outcome, dispatched_at) VALUES
                (1, 'S', 12, 'transported',     '2026-09-03 14:20:00'),
                (2, 'P', 8,  'treated_on_site', '2026-09-10 02:05:00'),
                (3, 'P', 5,  'cancelled',       '2026-09-11 09:00:00'),
                (4, 'S', 20, 'transported',     '2026-10-01 00:30:00'),
                (5, 'P', 3,  'transported',     '2026-09-28 18:45:00');
            SQL);
        $this->dispatches = new Dispatches($connection, new Tariffs($connection));
    }

    #[Test]
    public function cancelledDispatchIsNotBillable(): void
    {
        self::assertTrue($this->dispatches->isBillable(1));
        self::assertTrue($this->dispatches->isBillable(2));
        self::assertFalse($this->dispatches->isBillable(3));
    }

    #[Test]
    public function dispatchIsPricedWithTariffOfItsCrewType(): void
    {
        self::assertSame(30000 + 12 * 450, $this->dispatches->priceInCents(1));
        self::assertSame(20000 + 8 * 350, $this->dispatches->priceInCents(2));
    }

    #[Test]
    public function monthlySettlementSumsBillableDispatchesOfThatMonthPerCrewType(): void
    {
        $settlement = $this->dispatches->settlementForMonth(2026, 9);

        // P: dispatch 2 (20000 + 8 * 350) + dispatch 5 (20000 + 3 * 350); dispatch 3 cancelled, dispatch 4 is October
        self::assertSame(['P' => 22800 + 21050, 'S' => 30000 + 12 * 450], $settlement);
    }
}
