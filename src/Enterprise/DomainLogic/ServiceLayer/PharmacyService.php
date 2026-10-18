<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\DomainLogic\ServiceLayer;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;

final readonly class PharmacyService
{
    public function __construct(
        private PrescriptionRepository $prescriptions,
        private MedicationRepository $medications,
        private SaleRepository $sales,
        private TransactionManager $transactions,
    ) {
    }

    #[\NoDiscard('the amount to pay must be handed to the cash register')]
    public function dispensePrescription(int $prescriptionId, DateTimeImmutable $today): int
    {
        return $this->transactions->transactional(function () use ($prescriptionId, $today): int {
            $prescription = $this->prescriptions->get($prescriptionId);

            $sale = $prescription->dispense($today);

            $this->prescriptions->save($prescription);
            $this->recordSale($sale);

            return $sale->totalInCents;
        });
    }

    #[\NoDiscard('the amount to pay must be handed to the cash register')]
    public function sellOverTheCounter(string $ean, int $quantity, DateTimeImmutable $today): int
    {
        return $this->transactions->transactional(function () use ($ean, $quantity, $today): int {
            $medication = $this->medications->get($ean);

            $sale = Sale::overTheCounter($medication, $quantity, $today);

            $this->recordSale($sale);

            return $sale->totalInCents;
        });
    }

    private function recordSale(Sale $sale): void
    {
        foreach ($sale->lines as $saleLine) {
            $this->medications->save($saleLine->medication);
        }
        $this->sales->add($sale);
    }
}
