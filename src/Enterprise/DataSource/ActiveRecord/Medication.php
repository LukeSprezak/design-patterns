<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\ActiveRecord;

use PDO;

/** Data, business rules and the SQL that persists them, all in one class. */
final class Medication
{
    public function __construct(
        private readonly PDO $connection,
        public readonly string $ean,
        public private(set) string $name,
        public private(set) int $priceInCents,
        public private(set) int $stock,
    ) {
    }

    public static function find(PDO $connection, string $ean): ?self
    {
        $statement = $connection->prepare('SELECT ean, name, price_in_cents, stock FROM medications WHERE ean = ?');
        $statement->execute([$ean]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : new self(
            $connection,
            ean: $row['ean'],
            name: $row['name'],
            priceInCents: $row['price_in_cents'],
            stock: $row['stock'],
        );
    }

    #[\NoDiscard('the amount to pay must be handed to the cash register')]
    public function dispense(int $quantity): int
    {
        if ($quantity > $this->stock) {
            throw InsufficientStockException::for($this, $quantity);
        }
        $this->stock -= $quantity;

        return $quantity * $this->priceInCents;
    }

    public function restock(int $quantity): void
    {
        $this->stock += $quantity;
    }

    public function save(): void
    {
        $this->connection
            ->prepare(<<<SQL
                INSERT INTO medications (ean, name, price_in_cents, stock) VALUES (?, ?, ?, ?)
                ON CONFLICT (ean) DO UPDATE SET name = excluded.name, price_in_cents = excluded.price_in_cents, stock = excluded.stock
                SQL)
            ->execute([$this->ean, $this->name, $this->priceInCents, $this->stock]);
    }
}
