<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\FactoryMethod;

use DesignPatterns\Creational\FactoryMethod\Farmacol\FarmacolPriceListImporter;
use DesignPatterns\Creational\FactoryMethod\Neuca\NeucaPriceListImporter;
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
        $priceListCsv = <<<CSV
        EAN;NAME;NET_PRICE;VAT
        5901234123457;Apap 500mg 12 tablets;7,49;8
        5909998887776;Ibuprom Max 400mg 24 tablets;15,90;8
        CSV;

        $importResult = new NeucaPriceListImporter()->import($priceListCsv);

        self::assertEquals(
            [
                new PriceListEntry('5901234123457', 'Apap 500mg 12 tablets', 749, 8),
                new PriceListEntry('5909998887776', 'Ibuprom Max 400mg 24 tablets', 1590, 8),
            ],
            $importResult->importedEntries,
        );
        self::assertSame([], $importResult->rejectionReasons);
    }

    #[Test]
    public function rejectsEntryWithInvalidEanChecksum(): void
    {
        $priceListCsv = <<<CSV
        EAN;NAME;NET_PRICE;VAT
        5901234123450;Apap 500mg 12 tablets;7,49;8
        CSV;

        $importResult = new NeucaPriceListImporter()->import($priceListCsv);

        self::assertSame([], $importResult->importedEntries);
        self::assertSame(['5901234123450: invalid EAN code'], $importResult->rejectionReasons);
    }

    #[Test]
    public function rejectsDuplicatedEanKeepingFirstOccurrence(): void
    {
        $priceListCsv = <<<CSV
        EAN;NAME;NET_PRICE;VAT
        5901234123457;Apap 500mg 12 tablets;7,49;8
        5901234123457;Apap 500mg 12 tablets;7,99;8
        CSV;

        $importResult = new NeucaPriceListImporter()->import($priceListCsv);

        self::assertEquals(
            [new PriceListEntry('5901234123457', 'Apap 500mg 12 tablets', 749, 8)],
            $importResult->importedEntries,
        );
        self::assertSame(['5901234123457: duplicated EAN code'], $importResult->rejectionReasons);
    }

    #[Test]
    #[DataProvider('wholesalerPriceLists')]
    public function sameImportRulesApplyToEveryWholesaler(
        PriceListImporter $priceListImporter,
        string $priceListFileContent,
    ): void {
        $importResult = $priceListImporter->import($priceListFileContent);

        self::assertCount(1, $importResult->importedEntries);
        self::assertSame(
            ['5901234123457: duplicated EAN code', '1234567890123: invalid EAN code'],
            $importResult->rejectionReasons,
        );
    }

    /** @return iterable<string, array{PriceListImporter, string}> */
    public static function wholesalerPriceLists(): iterable
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
