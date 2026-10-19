<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DataSource\DataMapper;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\PrescriptionItem;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\RefundLevel;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\PrescriptionRepository;
use PDO;

/** Assembles the Prescription aggregate (header, items, their medications) from three tables and writes its changes back. */
final readonly class PrescriptionMapper implements PrescriptionRepository
{
    private MedicationMapper $medications;

    public function __construct(private PDO $connection)
    {
        $this->medications = new MedicationMapper($connection);
    }

    #[\Override]
    public function get(int $prescriptionId): Prescription
    {
        $statement = $this->connection->prepare('SELECT valid_until, dispensed_at FROM prescriptions WHERE id = ?');
        $statement->execute([$prescriptionId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            throw RecordNotFoundException::prescription($prescriptionId);
        }

        return new Prescription(
            id: $prescriptionId,
            validUntil: new DateTimeImmutable($row['valid_until']),
            items: $this->loadItems($prescriptionId),
            dispensedAt: $row['dispensed_at'] === null ? null : new DateTimeImmutable($row['dispensed_at']),
        );
    }

    /** Only dispensed_at changes after a prescription has been issued, so that is all save() writes. */
    #[\Override]
    public function save(Prescription $prescription): void
    {
        $this->connection
            ->prepare('UPDATE prescriptions SET dispensed_at = ? WHERE id = ?')
            ->execute([$prescription->dispensedAt?->format('Y-m-d H:i:s'), $prescription->id]);
    }

    /** @return list<PrescriptionItem> */
    private function loadItems(int $prescriptionId): array
    {
        $statement = $this->connection->prepare(
            'SELECT ean, quantity, refund_level FROM prescription_items WHERE prescription_id = ? ORDER BY rowid',
        );
        $statement->execute([$prescriptionId]);

        return array_map(
            fn (array $row): PrescriptionItem => new PrescriptionItem(
                medication: $this->medications->get($row['ean']),
                quantity: $row['quantity'],
                refundLevel: RefundLevel::from($row['refund_level']),
            ),
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }
}
