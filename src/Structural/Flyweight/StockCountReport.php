<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Flyweight;

use DateTimeImmutable;

final readonly class StockCountReport
{
    /** @param list<StockLot> $stockLots */
    public function __construct(
        private array $stockLots,
    ) {
    }

    public function totalValueInCents(): int
    {
        return array_sum(array_map(static fn (StockLot $stockLot): int => $stockLot->product->valueInCents($stockLot->quantity), $this->stockLots));
    }

    /** @return list<StockLot> */
    public function lotsExpiringBefore(DateTimeImmutable $date): array
    {
        return array_values(array_filter($this->stockLots, static fn (StockLot $stockLot): bool => $stockLot->expiryDate < $date));
    }

    /** @return list<StockLot> */
    public function lotsStoredInWrongZone(): array
    {
        return array_values(array_filter($this->stockLots, static fn (StockLot $stockLot): bool => !$stockLot->product->canBeStoredIn($stockLot->storageZone)));
    }
}
