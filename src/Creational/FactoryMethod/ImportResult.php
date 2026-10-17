<?php

declare(strict_types=1);

namespace DesignPatterns\Creational\FactoryMethod;

final readonly class ImportResult
{
    /**
     * @param list<PriceListEntry> $importedEntries
     * @param list<string> $rejectionReasons
     */
    public function __construct(
        public array $importedEntries,
        public array $rejectionReasons,
    ) {
    }
}
