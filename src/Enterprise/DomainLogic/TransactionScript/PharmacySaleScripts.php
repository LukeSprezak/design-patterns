<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\TransactionScript;

use DateTimeImmutable;
use PDO;
use Throwable;

final readonly class PharmacySaleScripts
{
    private const int FLAT_FEE_IN_CENTS = 320;

    public function __construct(
        private PDO $connection,
    ) {
    }

    #[\NoDiscard('the amount to pay must be handed to the cash register')]
    public function dispensePrescription(int $prescriptionId, DateTimeImmutable $today): int
    {
        $this->connection->beginTransaction();
        try {
            $prescription = $this->fetchOne(
                'SELECT id, valid_until, dispensed_at FROM prescriptions WHERE id = :id',
                ['id' => $prescriptionId],
            ) ?? throw DispensingException::prescriptionNotFound($prescriptionId);

            if ($prescription['dispensed_at'] !== null) {
                throw DispensingException::alreadyDispensed($prescriptionId, $prescription['dispensed_at']);
            }
            if ($prescription['valid_until'] < $today->format('Y-m-d')) {
                throw DispensingException::expired($prescriptionId, $prescription['valid_until']);
            }

            $prescriptionItems = $this->fetchAll(
                <<<SQL
                SELECT m.ean, m.name, m.price_in_cents, m.stock, pi.quantity, pi.refund_level
                FROM prescription_items pi
                JOIN medications m ON m.ean = pi.ean
                WHERE pi.prescription_id = :prescription_id
                SQL,
                ['prescription_id' => $prescriptionId],
            );

            $totalInCents = 0;
            $saleItems = [];
            foreach ($prescriptionItems as $prescriptionItem) {
                if ($prescriptionItem['stock'] < $prescriptionItem['quantity']) {
                    throw DispensingException::insufficientStock(
                        $prescriptionItem['name'],
                        $prescriptionItem['quantity'],
                        $prescriptionItem['stock'],
                    );
                }
                $this->execute(
                    'UPDATE medications SET stock = stock - :quantity WHERE ean = :ean',
                    ['quantity' => $prescriptionItem['quantity'], 'ean' => $prescriptionItem['ean']],
                );

                $patientPricePerPackageInCents = match ($prescriptionItem['refund_level']) {
                    'B' => 0,
                    'R' => self::FLAT_FEE_IN_CENTS,
                    '30' => intdiv($prescriptionItem['price_in_cents'] * 30, 100),
                    '50' => intdiv($prescriptionItem['price_in_cents'] * 50, 100),
                    '100' => $prescriptionItem['price_in_cents'],
                };
                $totalInCents += $patientPricePerPackageInCents * $prescriptionItem['quantity'];
                $saleItems[] = [
                    'ean' => $prescriptionItem['ean'],
                    'quantity' => $prescriptionItem['quantity'],
                    'price_in_cents' => $patientPricePerPackageInCents,
                ];
            }

            $saleId = $this->insertSale($prescriptionId, $totalInCents, $today);
            foreach ($saleItems as $saleItem) {
                $this->insertSaleItem($saleId, $saleItem);
            }
            $this->execute(
                'UPDATE prescriptions SET dispensed_at = :dispensed_at WHERE id = :id',
                ['dispensed_at' => $today->format('Y-m-d H:i:s'), 'id' => $prescriptionId],
            );

            $this->connection->commit();

            return $totalInCents;
        } catch (Throwable $failure) {
            $this->connection->rollBack();
            throw $failure;
        }
    }

    #[\NoDiscard('the amount to pay must be handed to the cash register')]
    public function sellOverTheCounter(string $ean, int $quantity, DateTimeImmutable $today): int
    {
        $this->connection->beginTransaction();
        try {
            $medication = $this->fetchOne(
                'SELECT name, price_in_cents, stock FROM medications WHERE ean = :ean',
                ['ean' => $ean],
            ) ?? throw DispensingException::medicationNotFound($ean);
            if ($medication['stock'] < $quantity) {
                throw DispensingException::insufficientStock($medication['name'], $quantity, $medication['stock']);
            }
            $this->execute(
                'UPDATE medications SET stock = stock - :quantity WHERE ean = :ean',
                ['quantity' => $quantity, 'ean' => $ean],
            );

            $totalInCents = $medication['price_in_cents'] * $quantity;
            $saleId = $this->insertSale(null, $totalInCents, $today);
            $this->insertSaleItem($saleId, [
                'ean' => $ean,
                'quantity' => $quantity,
                'price_in_cents' => $medication['price_in_cents'],
            ]);

            $this->connection->commit();

            return $totalInCents;
        } catch (Throwable $failure) {
            $this->connection->rollBack();
            throw $failure;
        }
    }

    private function insertSale(?int $prescriptionId, int $totalInCents, DateTimeImmutable $soldAt): int
    {
        $this->execute(
            'INSERT INTO sales (prescription_id, total_in_cents, sold_at) VALUES (:prescription_id, :total_in_cents, :sold_at)',
            ['prescription_id' => $prescriptionId, 'total_in_cents' => $totalInCents, 'sold_at' => $soldAt->format('Y-m-d H:i:s')],
        );

        return (int) $this->connection->lastInsertId();
    }

    /** @param array{ean: string, quantity: int, price_in_cents: int} $saleItem */
    private function insertSaleItem(int $saleId, array $saleItem): void
    {
        $this->execute(
            'INSERT INTO sale_items (sale_id, ean, quantity, price_in_cents) VALUES (:sale_id, :ean, :quantity, :price_in_cents)',
            ['sale_id' => $saleId, ...$saleItem],
        );
    }

    /**
     * @param array<string, scalar|null> $parameters
     * @return array<string, mixed>|null
     */
    private function fetchOne(string $sql, array $parameters): ?array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, scalar|null> $parameters
     * @return list<array<string, mixed>>
     */
    private function fetchAll(string $sql, array $parameters): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param array<string, scalar|null> $parameters */
    private function execute(string $sql, array $parameters): void
    {
        $this->connection->prepare($sql)->execute($parameters);
    }
}
