<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod;

final class NeucaCsvParser implements PriceListParser
{
    #[\Override]
    public function parse(string $fileContent): array
    {
        return $fileContent
            |> trim(...)
            |> (static fn(string $trimmedContent): array => explode("\n", $trimmedContent))
            |> (static fn(array $rowsIncludingHeader): array => array_slice($rowsIncludingHeader, 1))
            |> (fn(array $dataRows): array => array_map($this->parseDataRow(...), $dataRows));
    }

    private function parseDataRow(string $dataRow): PriceListEntry
    {
        [$ean, $productName, $netPriceWithCommaDecimal, $vatRate] = explode(';', $dataRow);

        return new PriceListEntry(
            $ean,
            $productName,
            (int) round((float) str_replace(',', '.', $netPriceWithCommaDecimal) * 100),
            (int) $vatRate,
        );
    }
}
