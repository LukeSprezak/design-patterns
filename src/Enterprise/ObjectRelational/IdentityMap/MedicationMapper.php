<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\IdentityMap;

use DesignPatterns\Enterprise\DataSource\DataMapper\RecordNotFoundException;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\MedicationRepository;
use PDO;

/** The Data Mapper's MedicationMapper with an Identity Map in front of every read. */
final readonly class MedicationMapper implements MedicationRepository
{
    public function __construct(
        private PDO $connection,
        private IdentityMap $identityMap,
    ) {
    }

    #[\Override]
    public function get(string $ean): Medication
    {
        return $this->identityMap->get(Medication::class, $ean) ?? $this->load($ean);
    }

    #[\Override]
    public function save(Medication $medication): void
    {
        $this->connection
            ->prepare('UPDATE medications SET stock = ? WHERE ean = ?')
            ->execute([$medication->stock, $medication->ean]);
    }

    private function load(string $ean): Medication
    {
        $statement = $this->connection->prepare('SELECT ean, name, price_in_cents, stock FROM medications WHERE ean = ?');
        $statement->execute([$ean]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw RecordNotFoundException::medication($ean);
        }

        $medication = new Medication(
            ean: $row['ean'],
            name: $row['name'],
            priceInCents: $row['price_in_cents'],
            stock: $row['stock'],
        );
        $this->identityMap->add($ean, $medication);

        return $medication;
    }
}
