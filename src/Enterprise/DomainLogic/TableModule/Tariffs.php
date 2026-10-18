<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TableModule;

use PDO;

final readonly class Tariffs
{
    public function __construct(private PDO $connection)
    {
    }

    public function priceInCents(string $crewType, int $kilometers): int
    {
        $statement = $this->connection->prepare(
            'SELECT base_fee_in_cents, fee_per_kilometer_in_cents FROM tariffs WHERE crew_type = ?',
        );
        $statement->execute([$crewType]);
        $tariff = $statement->fetch(PDO::FETCH_ASSOC);

        return $tariff['base_fee_in_cents'] + $kilometers * $tariff['fee_per_kilometer_in_cents'];
    }
}
