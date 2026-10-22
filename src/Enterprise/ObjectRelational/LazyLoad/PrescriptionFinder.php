<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\LazyLoad;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\PrescriptionRepository;
use PDO;
use ReflectionClass;

/**
 * Lists prescriptions with one query and hands out native lazy proxies (PHP 8.4+): id and validUntil
 * are filled from the list row, everything else (items, medications, dispensedAt) is loaded through
 * the Data Mapper the first time it is touched.
 */
final readonly class PrescriptionFinder
{
    public function __construct(
        private PDO $connection,
        private PrescriptionRepository $prescriptions,
    ) {
    }

    /** @return list<Prescription> */
    public function findValidOn(DateTimeImmutable $today): array
    {
        $statement = $this->connection->prepare(
            'SELECT id, valid_until FROM prescriptions WHERE valid_until >= ? AND dispensed_at IS NULL ORDER BY id',
        );
        $statement->execute([$today->format('Y-m-d')]);

        return array_map(
            fn (array $row): Prescription => $this->lazyProxy($row['id'], new DateTimeImmutable($row['valid_until'])),
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    private function lazyProxy(int $prescriptionId, DateTimeImmutable $validUntil): Prescription
    {
        $prescriptionClass = new ReflectionClass(Prescription::class);

        $proxy = $prescriptionClass->newLazyProxy(
            fn (): Prescription => $this->prescriptions->get($prescriptionId),
        );
        $prescriptionClass->getProperty('id')->setRawValueWithoutLazyInitialization($proxy, $prescriptionId);
        $prescriptionClass->getProperty('validUntil')->setRawValueWithoutLazyInitialization($proxy, $validUntil);

        return $proxy;
    }
}
