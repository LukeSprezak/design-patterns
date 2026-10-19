<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\TableModule;

use DesignPatterns\Enterprise\DomainLogic\TableModule\CrewType;
use DesignPatterns\Enterprise\DomainLogic\TableModule\EmergencyDispatchSchema;
use DesignPatterns\Enterprise\DomainLogic\TableModule\Tariffs;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Tariffs::class)]
#[CoversClass(EmergencyDispatchSchema::class)]
final class TariffsTest extends TestCase
{
    private Tariffs $tariffs;

    protected function setUp(): void
    {
        $connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        EmergencyDispatchSchema::create($connection);
        $connection->exec(<<<SQL
            INSERT INTO tariffs (crew_type, base_fee_in_cents, fee_per_kilometer_in_cents) VALUES
                ('S', 30000, 450),
                ('P', 20000, 350);
            SQL);
        $this->tariffs = new Tariffs($connection);
    }

    #[Test]
    public function priceIsBaseFeeOfCrewTypePlusFeePerKilometer(): void
    {
        self::assertSame(30000 + 12 * 450, $this->tariffs->priceInCents(CrewType::Specialist, 12));
        self::assertSame(20000 + 8 * 350, $this->tariffs->priceInCents(CrewType::Basic, 8));
    }
}
