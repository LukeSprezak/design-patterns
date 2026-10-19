<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\TableDataGateway;

use PDO;

/**
 * One instance per table, one method per SQL statement; rows go in and out as plain arrays.
 *
 * @phpstan-type MedicationRow array{ean: string, name: string, price_in_cents: int, stock: int}
 */
final readonly class MedicationGateway
{
    public function __construct(private PDO $connection)
    {
    }

    /** @return MedicationRow|null */
    public function find(string $ean): ?array
    {
        $statement = $this->connection->prepare('SELECT ean, name, price_in_cents, stock FROM medications WHERE ean = ?');
        $statement->execute([$ean]);

        return $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @return list<MedicationRow> */
    public function findBelowStock(int $threshold): array
    {
        $statement = $this->connection->prepare(
            'SELECT ean, name, price_in_cents, stock FROM medications WHERE stock < ? ORDER BY name',
        );
        $statement->execute([$threshold]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(string $ean, string $name, int $priceInCents, int $stock): void
    {
        $this->connection
            ->prepare('INSERT INTO medications (ean, name, price_in_cents, stock) VALUES (?, ?, ?, ?)')
            ->execute([$ean, $name, $priceInCents, $stock]);
    }

    public function updateStock(string $ean, int $stock): void
    {
        $this->connection
            ->prepare('UPDATE medications SET stock = ? WHERE ean = ?')
            ->execute([$stock, $ean]);
    }

    public function delete(string $ean): void
    {
        $this->connection
            ->prepare('DELETE FROM medications WHERE ean = ?')
            ->execute([$ean]);
    }
}
