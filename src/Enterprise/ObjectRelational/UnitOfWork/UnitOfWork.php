<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\UnitOfWork;

use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\MedicationRepository;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\PrescriptionRepository;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\SaleRepository;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\TransactionManager;

/**
 * Collects the objects touched by one business transaction and writes each of them exactly once,
 * inside one database transaction, when commit() is called.
 */
final class UnitOfWork
{
    /** @var array<int, Medication|Prescription> keyed by object id, so registering twice writes once */
    private array $dirtyObjects = [];

    /** @var list<Sale> */
    private array $newSales = [];

    public function __construct(
        private readonly MedicationRepository $medications,
        private readonly PrescriptionRepository $prescriptions,
        private readonly SaleRepository $sales,
        private readonly TransactionManager $transactions,
    ) {
    }

    public function registerDirty(Medication|Prescription $object): void
    {
        $this->dirtyObjects[spl_object_id($object)] = $object;
    }

    public function registerNew(Sale $sale): void
    {
        $this->newSales[] = $sale;
    }

    public function commit(): void
    {
        $this->transactions->transactional(function (): void {
            foreach ($this->dirtyObjects as $dirtyObject) {
                match (true) {
                    $dirtyObject instanceof Medication => $this->medications->save($dirtyObject),
                    $dirtyObject instanceof Prescription => $this->prescriptions->save($dirtyObject),
                };
            }
            foreach ($this->newSales as $newSale) {
                $this->sales->add($newSale);
            }
        });

        $this->dirtyObjects = [];
        $this->newSales = [];
    }
}
