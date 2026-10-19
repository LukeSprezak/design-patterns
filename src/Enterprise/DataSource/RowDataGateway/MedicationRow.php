<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\RowDataGateway;

use PDO;

/** One object per row of the medications table: the columns as fields, plus the SQL that writes this row. */
final class MedicationRow
{
    public function __construct(
        private readonly PDO $connection,
        public readonly string $ean,
        public string $name,
        public int $priceInCents,
        public int $stock,
    ) {
    }

    public function insert(): void
    {
        $this->connection
            ->prepare('INSERT INTO medications (ean, name, price_in_cents, stock) VALUES (?, ?, ?, ?)')
            ->execute([$this->ean, $this->name, $this->priceInCents, $this->stock]);
    }

    public function update(): void
    {
        $this->connection
            ->prepare('UPDATE medications SET name = ?, price_in_cents = ?, stock = ? WHERE ean = ?')
            ->execute([$this->name, $this->priceInCents, $this->stock, $this->ean]);
    }

    public function delete(): void
    {
        $this->connection
            ->prepare('DELETE FROM medications WHERE ean = ?')
            ->execute([$this->ean]);
    }
}
