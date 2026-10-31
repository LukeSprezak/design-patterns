<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Flyweight;

use DateTimeImmutable;
use DesignPatterns\Structural\Flyweight\Product;
use DesignPatterns\Structural\Flyweight\ProductPool;
use DesignPatterns\Structural\Flyweight\StockCountReport;
use DesignPatterns\Structural\Flyweight\StockLot;
use DesignPatterns\Structural\Flyweight\WmsStockExportReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WmsStockExportReader::class)]
#[CoversClass(ProductPool::class)]
#[CoversClass(Product::class)]
#[CoversClass(StockCountReport::class)]
final class StockCountTest extends TestCase
{
    private const string WMS_STOCK_EXPORT = <<<'CSV'
        ean;name;manufacturer;atc_code;wholesale_price_in_cents;minimum_temperature;maximum_temperature;lot_number;expiry_date;location_code;storage_zone;quantity
        5909990000017;Insulin Aspart 100 U/ml;Novo Nordisk;A10AB05;3150;2;8;NN4521;2027-06-30;C-01-03;COLD;40
        5909990000017;Insulin Aspart 100 U/ml;Novo Nordisk;A10AB05;3150;2;8;NN4588;2027-09-30;A-12-07;AMBIENT;10
        5909990000024;Paracetamol 500 mg 50 tablets;Polpharma;N02BE01;450;15;25;PP1102;2027-01-15;A-03-01;AMBIENT;200
        CSV;

    #[Test]
    public function lotsOfSameProductShareOneProductInstance(): void
    {
        $stockLots = self::readStockLots();

        self::assertSame($stockLots[0]->product, $stockLots[1]->product);
        self::assertNotSame($stockLots[0]->product, $stockLots[2]->product);
    }

    #[Test]
    public function totalValueSumsEveryLotAtWholesalePrice(): void
    {
        self::assertSame(247500, new StockCountReport(self::readStockLots())->totalValueInCents());
    }

    #[Test]
    public function lotsExpiringBeforeGivenDateAreListed(): void
    {
        $expiringStockLots = new StockCountReport(self::readStockLots())->lotsExpiringBefore(new DateTimeImmutable('2027-01-31'));

        self::assertSame(['PP1102'], array_map(static fn (StockLot $stockLot): string => $stockLot->lotNumber, $expiringStockLots));
    }

    #[Test]
    public function coldChainProductOnAmbientShelfIsStoredInWrongZone(): void
    {
        $wronglyStoredStockLots = new StockCountReport(self::readStockLots())->lotsStoredInWrongZone();

        self::assertSame(['NN4588'], array_map(static fn (StockLot $stockLot): string => $stockLot->lotNumber, $wronglyStoredStockLots));
    }

    /** @return list<StockLot> */
    private static function readStockLots(): array
    {
        $wmsStockExport = new \SplTempFileObject();
        $wmsStockExport->fwrite(self::WMS_STOCK_EXPORT);

        return new WmsStockExportReader(new ProductPool())->read($wmsStockExport);
    }
}
