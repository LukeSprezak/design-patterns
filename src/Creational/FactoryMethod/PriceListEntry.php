<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod;

final readonly class PriceListEntry
{
    public function __construct(
        public string $ean,
        public string $productName,
        public int $netPriceInCents,
        public int $vatRate,
    ) {
    }
}
