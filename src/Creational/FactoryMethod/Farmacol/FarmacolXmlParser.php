<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod\Farmacol;

use DesignPatterns\Creational\FactoryMethod\PriceListEntry;
use DesignPatterns\Creational\FactoryMethod\PriceListParser;
use SimpleXMLElement;

final class FarmacolXmlParser implements PriceListParser
{
    /**
     * @throws \Exception
     */
    #[\Override]
    public function parse(string $fileContent): array
    {
        $priceListEntries = [];
        foreach (new SimpleXMLElement($fileContent)->item as $itemElement) {
            $priceListEntries[] = new PriceListEntry(
                (string) $itemElement['ean'],
                (string) $itemElement['name'],
                (int) round((float) $itemElement['netPrice'] * 100),
                (int) $itemElement['vat'],
            );
        }

        return $priceListEntries;
    }
}
