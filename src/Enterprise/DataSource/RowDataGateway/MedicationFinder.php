<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\RowDataGateway;

use PDO;

/** The read side of the gateway: runs SELECTs and turns each row into a MedicationRow. */
final readonly class MedicationFinder
{
    public function __construct(private PDO $connection)
    {
    }

    public function find(string $ean): ?MedicationRow
    {
        $statement = $this->connection->prepare('SELECT ean, name, price_in_cents, stock FROM medications WHERE ean = ?');
        $statement->execute([$ean]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->load($row);
    }

    /** @return list<MedicationRow> */
    public function findBelowStock(int $threshold): array
    {
        $statement = $this->connection->prepare(
            'SELECT ean, name, price_in_cents, stock FROM medications WHERE stock < ? ORDER BY name',
        );
        $statement->execute([$threshold]);

        return array_map($this->load(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @param array{ean: string, name: string, price_in_cents: int, stock: int} $row */
    private function load(array $row): MedicationRow
    {
        return new MedicationRow(
            $this->connection,
            ean: $row['ean'],
            name: $row['name'],
            priceInCents: $row['price_in_cents'],
            stock: $row['stock'],
        );
    }
}
