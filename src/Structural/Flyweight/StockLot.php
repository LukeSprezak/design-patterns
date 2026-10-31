<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Flyweight;

use DateTimeImmutable;

final readonly class StockLot
{
    public function __construct(
        public Product $product,
        public string $lotNumber,
        public DateTimeImmutable $expiryDate,
        public string $locationCode,
        public StorageZone $storageZone,
        public int $quantity,
    ) {
    }
}
