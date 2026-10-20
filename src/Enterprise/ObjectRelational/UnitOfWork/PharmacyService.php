<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\UnitOfWork;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\MedicationRepository;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\PrescriptionRepository;

/** The Service Layer's PharmacyService rewritten on top of a Unit of Work: register what changed, commit once. */
final readonly class PharmacyService
{
    public function __construct(
        private PrescriptionRepository $prescriptions,
        private MedicationRepository $medications,
        private UnitOfWork $unitOfWork,
    ) {
    }

    #[\NoDiscard('the amount to pay must be handed to the cash register')]
    public function dispensePrescription(int $prescriptionId, DateTimeImmutable $today): int
    {
        $prescription = $this->prescriptions->get($prescriptionId);

        $sale = $prescription->dispense($today);

        $this->unitOfWork->registerDirty($prescription);
        $this->registerSale($sale);
        $this->unitOfWork->commit();

        return $sale->totalInCents;
    }

    #[\NoDiscard('the amount to pay must be handed to the cash register')]
    public function sellOverTheCounter(string $ean, int $quantity, DateTimeImmutable $today): int
    {
        $medication = $this->medications->get($ean);

        $sale = Sale::overTheCounter($medication, $quantity, $today);

        $this->registerSale($sale);
        $this->unitOfWork->commit();

        return $sale->totalInCents;
    }

    private function registerSale(Sale $sale): void
    {
        foreach ($sale->lines as $saleLine) {
            $this->unitOfWork->registerDirty($saleLine->medication);
        }
        $this->unitOfWork->registerNew($sale);
    }
}
