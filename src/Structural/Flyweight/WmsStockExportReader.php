<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Flyweight;

use DateTimeImmutable;

final readonly class WmsStockExportReader
{
    public function __construct(
        private ProductPool $productPool,
    ) {
    }

    /** @return list<StockLot> */
    public function read(\SplFileObject $wmsStockExport): array
    {
        $wmsStockExport->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $wmsStockExport->setCsvControl(separator: ';', escape: '');

        $stockLots = [];
        foreach (new \LimitIterator($wmsStockExport, offset: 1) as $row) {
            [$ean, $name, $manufacturer, $atcCode, $wholesalePriceInCents, $minimumTemperature, $maximumTemperature, $lotNumber, $expiryDate, $locationCode, $storageZone, $quantity] = $row;

            $stockLots[] = new StockLot(
                product: $this->productPool->productFor($ean, static fn (): Product => new Product(
                    ean: $ean,
                    name: $name,
                    manufacturer: $manufacturer,
                    atcCode: $atcCode,
                    wholesalePriceInCents: (int) $wholesalePriceInCents,
                    minimumStorageTemperatureInCelsius: (int) $minimumTemperature,
                    maximumStorageTemperatureInCelsius: (int) $maximumTemperature,
                )),
                lotNumber: $lotNumber,
                expiryDate: new DateTimeImmutable($expiryDate),
                locationCode: $locationCode,
                storageZone: StorageZone::from($storageZone),
                quantity: (int) $quantity,
            );
        }

        return $stockLots;
    }
}
