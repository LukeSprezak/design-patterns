<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\DataMapper;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\SaleRepository;
use PDO;

/** Writes a Sale as one header row plus one sale_items row per line. */
final readonly class SaleMapper implements SaleRepository
{
    public function __construct(private PDO $connection)
    {
    }

    #[\Override]
    public function add(Sale $sale): void
    {
        $this->connection
            ->prepare('INSERT INTO sales (prescription_id, total_in_cents, sold_at) VALUES (?, ?, ?)')
            ->execute([$sale->prescriptionId, $sale->totalInCents, $sale->soldAt->format('Y-m-d H:i:s')]);
        $saleId = (int) $this->connection->lastInsertId();

        $insertLine = $this->connection->prepare(
            'INSERT INTO sale_items (sale_id, ean, quantity, price_in_cents) VALUES (?, ?, ?, ?)',
        );
        foreach ($sale->lines as $saleLine) {
            $insertLine->execute([$saleId, $saleLine->medication->ean, $saleLine->quantity, $saleLine->pricePerPackageInCents]);
        }
    }
}
