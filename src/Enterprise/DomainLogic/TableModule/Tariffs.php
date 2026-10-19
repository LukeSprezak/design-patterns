<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TableModule;

use PDO;

final readonly class Tariffs
{
    /** @var array<string, array{base_fee_in_cents: int, fee_per_kilometer_in_cents: int}> crew type value => tariff row */
    private array $rows;

    public function __construct(PDO $connection)
    {
        $this->rows = $connection
            ->query('SELECT crew_type, base_fee_in_cents, fee_per_kilometer_in_cents FROM tariffs')
            ->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    public function priceInCents(CrewType $crewType, int $kilometers): int
    {
        $tariff = $this->rows[$crewType->value];

        return $tariff['base_fee_in_cents'] + $kilometers * $tariff['fee_per_kilometer_in_cents'];
    }
}
