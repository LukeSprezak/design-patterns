<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TableModule;

use PDO;

final readonly class Dispatches
{
    public function __construct(
        private PDO $connection,
        private Tariffs $tariffs,
    ) {
    }

    public function isBillable(int $dispatchId): bool
    {
        return self::isBillableRow($this->fetchRow($dispatchId));
    }

    public function priceInCents(int $dispatchId): int
    {
        return $this->priceOfRow($this->fetchRow($dispatchId));
    }

    /** @return array<string, int> crew type => amount to invoice in cents, ordered by crew type */
    public function settlementForMonth(int $year, int $month): array
    {
        $statement = $this->connection->prepare(
            "SELECT crew_type, kilometers, outcome FROM dispatches WHERE strftime('%Y-%m', dispatched_at) = ? ORDER BY crew_type",
        );
        $statement->execute([sprintf('%04d-%02d', $year, $month)]);

        $settlement = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $dispatch) {
            if (self::isBillableRow($dispatch)) {
                $settlement[$dispatch['crew_type']] = ($settlement[$dispatch['crew_type']] ?? 0) + $this->priceOfRow($dispatch);
            }
        }

        return $settlement;
    }

    /** @param array{crew_type: string, kilometers: int, outcome: string} $dispatch */
    private static function isBillableRow(array $dispatch): bool
    {
        return $dispatch['outcome'] !== 'cancelled';
    }

    /** @param array{crew_type: string, kilometers: int, outcome: string} $dispatch */
    private function priceOfRow(array $dispatch): int
    {
        return $this->tariffs->priceInCents($dispatch['crew_type'], $dispatch['kilometers']);
    }

    /** @return array{crew_type: string, kilometers: int, outcome: string} */
    private function fetchRow(int $dispatchId): array
    {
        $statement = $this->connection->prepare('SELECT crew_type, kilometers, outcome FROM dispatches WHERE id = ?');
        $statement->execute([$dispatchId]);

        return $statement->fetch(PDO::FETCH_ASSOC);
    }
}
