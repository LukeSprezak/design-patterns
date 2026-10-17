<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod;

final class FarmacolPriceListImporter extends PriceListImporter
{
    #[\Override]
    protected function createPriceListParser(): PriceListParser
    {
        return new FarmacolXmlParser();
    }
}
