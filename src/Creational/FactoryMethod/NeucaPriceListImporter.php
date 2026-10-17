<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod;

final class NeucaPriceListImporter extends PriceListImporter
{
    #[\Override]
    protected function createPriceListParser(): PriceListParser
    {
        return new NeucaCsvParser();
    }
}
