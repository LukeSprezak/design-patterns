<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\FactoryMethod;

use DesignPatterns\Creational\FactoryMethod\FarmacolPriceListImporter;
use DesignPatterns\Creational\FactoryMethod\NeucaPriceListImporter;
use DesignPatterns\Creational\FactoryMethod\PriceListEntry;
use DesignPatterns\Creational\FactoryMethod\PriceListImporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PriceListImporter::class)]
#[CoversClass(NeucaPriceListImporter::class)]
#[CoversClass(FarmacolPriceListImporter::class)]
final class PriceListImporterTest extends TestCase
{
    #[Test]
    public function importsValidEntries(): void
    {
        $csv = <<<CSV
        EAN;NAME;NET_PRICE;VAT
        5901234123457;Apap 500mg 12 tablets;7,49;8
        5909998887776;Ibuprom Max 400mg 24 tablets;15,90;8
        CSV;

        $result = new NeucaPriceListImporter()->import($csv);

        self::assertEquals(
            [
                new PriceListEntry('5901234123457', 'Apap 500mg 12 tablets', 749, 8),
                new PriceListEntry('5909998887776', 'Ibuprom Max 400mg 24 tablets', 1590, 8),
            ],
            $result->imported,
        );
        self::assertSame([], $result->rejected);
    }

    #[Test]
    public function rejectsEntryWithInvalidEanChecksum(): void
    {
        $csv = <<<CSV
        EAN;NAME;NET_PRICE;VAT
        5901234123450;Apap 500mg 12 tablets;7,49;8
        CSV;

        $result = new NeucaPriceListImporter()->import($csv);

        self::assertSame([], $result->imported);
        self::assertSame(['5901234123450: invalid EAN code'], $result->rejected);
    }

    #[Test]
    public function rejectsDuplicatedEanKeepingFirstOccurrence(): void
    {
        $csv = <<<CSV
        EAN;NAME;NET_PRICE;VAT
        5901234123457;Apap 500mg 12 tablets;7,49;8
        5901234123457;Apap 500mg 12 tablets;7,99;8
        CSV;

        $result = new NeucaPriceListImporter()->import($csv);

        self::assertEquals(
            [new PriceListEntry('5901234123457', 'Apap 500mg 12 tablets', 749, 8)],
            $result->imported,
        );
        self::assertSame(['5901234123457: duplicated EAN code'], $result->rejected);
    }

    #[Test]
    #[DataProvider('wholesalers')]
    public function sameImportRulesApplyToEveryWholesaler(PriceListImporter $importer, string $content): void
    {
        $result = $importer->import($content);

        self::assertCount(1, $result->imported);
        self::assertSame(
            ['5901234123457: duplicated EAN code', '1234567890123: invalid EAN code'],
            $result->rejected,
        );
    }

    /** @return iterable<string, array{PriceListImporter, string}> */
    public static function wholesalers(): iterable
    {
        yield 'Neuca CSV' => [
            new NeucaPriceListImporter(),
            <<<CSV
            EAN;NAME;NET_PRICE;VAT
            5901234123457;Apap 500mg 12 tablets;7,49;8
            5901234123457;Apap 500mg 12 tablets;7,99;8
            1234567890123;Invalid;1,00;8
            CSV,
        ];

        yield 'Farmacol XML' => [
            new FarmacolPriceListImporter(),
            <<<XML
            <priceList>
                <item ean="5901234123457" name="Apap 500mg 12 tablets" netPrice="7.49" vat="8"/>
                <item ean="5901234123457" name="Apap 500mg 12 tablets" netPrice="7.99" vat="8"/>
                <item ean="1234567890123" name="Invalid" netPrice="1.00" vat="8"/>
            </priceList>
            XML,
        ];
    }
}
