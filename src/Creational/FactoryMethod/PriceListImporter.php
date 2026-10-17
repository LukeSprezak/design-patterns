<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod;

abstract class PriceListImporter
{
    #[\NoDiscard('the import result carries the list of rejected entries, which must be handled')]
    public function import(string $fileContent): ImportResult
    {
        $importedEntriesByEan = [];
        $rejectionReasons = [];

        foreach ($this->createPriceListParser()->parse($fileContent) as $priceListEntry) {
            if (!$this->isValidEanCode($priceListEntry->ean)) {
                $rejectionReasons[] = "{$priceListEntry->ean}: invalid EAN code";
                continue;
            }
            if (isset($importedEntriesByEan[$priceListEntry->ean])) {
                $rejectionReasons[] = "{$priceListEntry->ean}: duplicated EAN code";
                continue;
            }
            $importedEntriesByEan[$priceListEntry->ean] = $priceListEntry;
        }

        return new ImportResult(array_values($importedEntriesByEan), $rejectionReasons);
    }

    abstract protected function createPriceListParser(): PriceListParser;

    private function isValidEanCode(string $ean): bool
    {
        if (!preg_match('/^\d{13}$/', $ean)) {
            return false;
        }

        $weightedDigitSum = 0;
        for ($digitPosition = 0; $digitPosition < 12; $digitPosition++) {
            $weight = $digitPosition % 2 === 0 ? 1 : 3;
            $weightedDigitSum += (int) $ean[$digitPosition] * $weight;
        }
        $expectedCheckDigit = (10 - $weightedDigitSum % 10) % 10;

        return $expectedCheckDigit === (int) $ean[12];
    }
}
