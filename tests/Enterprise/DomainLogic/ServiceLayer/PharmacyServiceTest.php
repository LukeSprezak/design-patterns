<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\DispensingException;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\PrescriptionItem;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\RefundLevel;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\PharmacyService;
use DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake\InMemoryMedicationRepository;
use DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake\InMemoryPrescriptionRepository;
use DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake\InMemorySaleRepository;
use DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake\SpyTransactionManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PharmacyService::class)]
final class PharmacyServiceTest extends TestCase
{
    private InMemoryPrescriptionRepository $prescriptions;
    private InMemoryMedicationRepository $medications;
    private InMemorySaleRepository $sales;
    private SpyTransactionManager $transactions;
    private PharmacyService $pharmacyService;

    protected function setUp(): void
    {
        $apap = new Medication(ean: '5901234123457', name: 'Apap 500 mg', priceInCents: 749, stock: 10);
        $insulin = new Medication(ean: '5909998887776', name: 'Insulin Lantus', priceInCents: 12000, stock: 3);
        $amoxicillin = new Medication(ean: '5901112223330', name: 'Amoxicillin 500 mg', priceInCents: 2000, stock: 1);

        $this->medications = new InMemoryMedicationRepository();
        foreach ([$apap, $insulin, $amoxicillin] as $medication) {
            $this->medications->save($medication);
        }

        $this->prescriptions = new InMemoryPrescriptionRepository();
        $this->prescriptions->save(new Prescription(
            id: 1,
            validUntil: new DateTimeImmutable('2026-11-15'),
            items: [
                new PrescriptionItem($apap, quantity: 2, refundLevel: RefundLevel::Full),
                new PrescriptionItem($insulin, quantity: 1, refundLevel: RefundLevel::FlatFee),
                new PrescriptionItem($amoxicillin, quantity: 1, refundLevel: RefundLevel::FiftyPercent),
            ],
        ));
        $this->prescriptions->save(new Prescription(
            id: 2,
            validUntil: new DateTimeImmutable('2026-09-30'),
            items: [new PrescriptionItem($apap, quantity: 1, refundLevel: RefundLevel::Full)],
        ));

        $this->sales = new InMemorySaleRepository();
        $this->transactions = new SpyTransactionManager();
        $this->pharmacyService = new PharmacyService(
            $this->prescriptions,
            $this->medications,
            $this->sales,
            $this->transactions,
        );
    }

    #[Test]
    public function dispensingReturnsAmountToPayAndRecordsSaleInOneTransaction(): void
    {
        $amountToPayInCents = $this->pharmacyService->dispensePrescription(1, new DateTimeImmutable('2026-10-18'));

        self::assertSame(1498 + 320 + 1000, $amountToPayInCents);
        self::assertCount(1, $this->sales->sales);
        self::assertSame(1, $this->sales->sales[0]->prescriptionId);
        self::assertSame(8, $this->medications->get('5901234123457')->stock);
        self::assertNotNull($this->prescriptions->get(1)->dispensedAt);
        self::assertSame(1, $this->transactions->commits);
    }

    #[Test]
    public function rollsBackTransactionWhenDomainRuleRejectsDispensing(): void
    {
        try {
            (void) $this->pharmacyService->dispensePrescription(2, new DateTimeImmutable('2026-10-18'));
            self::fail('Expected DispensingException');
        } catch (DispensingException) {
        }

        self::assertSame(1, $this->transactions->rollbacks);
        self::assertSame(0, $this->transactions->commits);
        self::assertSame([], $this->sales->sales);
    }

    #[Test]
    public function overTheCounterSaleRecordsSaleWithoutPrescription(): void
    {
        $amountToPayInCents = $this->pharmacyService->sellOverTheCounter('5901234123457', 3, new DateTimeImmutable('2026-10-18'));

        self::assertSame(3 * 749, $amountToPayInCents);
        self::assertNull($this->sales->sales[0]->prescriptionId);
        self::assertSame(7, $this->medications->get('5901234123457')->stock);
        self::assertSame(1, $this->transactions->commits);
    }
}
