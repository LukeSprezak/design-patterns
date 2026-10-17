<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\FactoryMethod;

use DesignPatterns\Creational\FactoryMethod\FarmacolXmlParser;
use DesignPatterns\Creational\FactoryMethod\PriceListEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FarmacolXmlParser::class)]
final class FarmacolXmlParserTest extends TestCase
{
    #[Test]
    public function parsesItemElementsWithDotDecimalPrice(): void
    {
        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <priceList>
            <item ean="5901234123457" name="Apap 500mg 12 tablets" netPrice="7.49" vat="8"/>
            <item ean="5901112223330" name="Rutinoscorbin 90 tablets" netPrice="21.00" vat="8"/>
        </priceList>
        XML;

        $entries = new FarmacolXmlParser()->parse($xml);

        self::assertEquals(
            [
                new PriceListEntry('5901234123457', 'Apap 500mg 12 tablets', 749, 8),
                new PriceListEntry('5901112223330', 'Rutinoscorbin 90 tablets', 2100, 8),
            ],
            $entries,
        );
    }
}
