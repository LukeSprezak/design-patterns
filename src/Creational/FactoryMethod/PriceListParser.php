<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod;

interface PriceListParser
{
    /** @return list<PriceListEntry> */
    public function parse(string $fileContent): array;
}
