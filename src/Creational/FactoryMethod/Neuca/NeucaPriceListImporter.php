<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod\Neuca;

use DesignPatterns\Creational\FactoryMethod\PriceListImporter;
use DesignPatterns\Creational\FactoryMethod\PriceListParser;

final class NeucaPriceListImporter extends PriceListImporter
{
    #[\Override]
    protected function createPriceListParser(): PriceListParser
    {
        return new NeucaCsvParser();
    }
}
