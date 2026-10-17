<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\FactoryMethod;

use DesignPatterns\Creational\FactoryMethod\NeucaCsvParser;
use DesignPatterns\Creational\FactoryMethod\PriceListEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NeucaCsvParser::class)]
final class NeucaCsvParserTest extends TestCase
{
    #[Test]
    public function parsesSemicolonSeparatedRowsWithCommaDecimalPrice(): void
    {
        $csv = <<<CSV
        EAN;NAME;NET_PRICE;VAT
        5901234123457;Apap 500mg 12 tablets;7,49;8
        5909998887776;Ibuprom Max 400mg 24 tablets;15,90;8
        CSV;

        $entries = new NeucaCsvParser()->parse($csv);

        self::assertEquals(
            [
                new PriceListEntry('5901234123457', 'Apap 500mg 12 tablets', 749, 8),
                new PriceListEntry('5909998887776', 'Ibuprom Max 400mg 24 tablets', 1590, 8),
            ],
            $entries,
        );
    }
}
