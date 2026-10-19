<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\DataMapper;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\MedicationRepository;
use PDO;

/** Moves data between the medications table and the Domain Model's Medication, which knows nothing about SQL. */
final readonly class MedicationMapper implements MedicationRepository
{
    public function __construct(private PDO $connection)
    {
    }

    #[\Override]
    public function get(string $ean): Medication
    {
        $statement = $this->connection->prepare('SELECT ean, name, price_in_cents, stock FROM medications WHERE ean = ?');
        $statement->execute([$ean]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw RecordNotFoundException::medication($ean);
        }

        return new Medication(
            ean: $row['ean'],
            name: $row['name'],
            priceInCents: $row['price_in_cents'],
            stock: $row['stock'],
        );
    }

    #[\Override]
    public function save(Medication $medication): void
    {
        $this->connection
            ->prepare('UPDATE medications SET stock = ? WHERE ean = ?')
            ->execute([$medication->stock, $medication->ean]);
    }
}
