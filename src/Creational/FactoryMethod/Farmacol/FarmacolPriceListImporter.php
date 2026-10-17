<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod\Farmacol;

use DesignPatterns\Creational\FactoryMethod\PriceListImporter;
use DesignPatterns\Creational\FactoryMethod\PriceListParser;

final class FarmacolPriceListImporter extends PriceListImporter
{
    #[\Override]
    protected function createPriceListParser(): PriceListParser
    {
        return new FarmacolXmlParser();
    }
}
