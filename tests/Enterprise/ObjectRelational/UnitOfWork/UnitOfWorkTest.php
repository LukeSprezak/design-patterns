<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\UnitOfWork;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Sale\Sale;
use DesignPatterns\Enterprise\ObjectRelational\UnitOfWork\UnitOfWork;
use DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake\InMemoryPrescriptionRepository;
use DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake\InMemorySaleRepository;
use DesignPatterns\Tests\Enterprise\DomainLogic\ServiceLayer\Fake\SpyTransactionManager;
use DesignPatterns\Tests\Enterprise\ObjectRelational\UnitOfWork\Fake\SpyMedicationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnitOfWork::class)]
final class UnitOfWorkTest extends TestCase
{
    private SpyMedicationRepository $medications;
    private InMemoryPrescriptionRepository $prescriptions;
    private InMemorySaleRepository $sales;
    private SpyTransactionManager $transactions;
    private UnitOfWork $unitOfWork;
    private Medication $insulin;
    private Prescription $prescription;

    protected function setUp(): void
    {
        $this->insulin = new Medication(ean: '5909998887776', name: 'Insulin Lantus', priceInCents: 12000, stock: 3);
        $this->prescription = new Prescription(id: 1, validUntil: new DateTimeImmutable('2026-11-15'), items: []);
        $this->medications = new SpyMedicationRepository($this->insulin);
        $this->prescriptions = new InMemoryPrescriptionRepository();
        $this->sales = new InMemorySaleRepository();
        $this->transactions = new SpyTransactionManager();
        $this->unitOfWork = new UnitOfWork($this->medications, $this->prescriptions, $this->sales, $this->transactions);
    }

    #[Test]
    public function nothingIsWrittenBeforeCommit(): void
    {
        $this->unitOfWork->registerDirty($this->insulin);
        $this->unitOfWork->registerNew(new Sale(null, [], new DateTimeImmutable('2026-10-19')));

        self::assertSame([], $this->medications->savedEans);
        self::assertSame([], $this->sales->sales);
        self::assertSame(0, $this->transactions->commits);
    }

    #[Test]
    public function commitWritesEveryRegisteredObjectExactlyOnceInsideOneTransaction(): void
    {
        $sale = new Sale(null, [], new DateTimeImmutable('2026-10-19'));
        $this->unitOfWork->registerDirty($this->insulin);
        $this->unitOfWork->registerDirty($this->insulin);
        $this->unitOfWork->registerDirty($this->prescription);
        $this->unitOfWork->registerNew($sale);

        $this->unitOfWork->commit();

        self::assertSame(['5909998887776'], $this->medications->savedEans);
        self::assertSame($this->prescription, $this->prescriptions->get(1));
        self::assertSame([$sale], $this->sales->sales);
        self::assertSame(1, $this->transactions->commits);
    }

    #[Test]
    public function commitClearsRegistrationsSoTheNextCommitWritesNothing(): void
    {
        $this->unitOfWork->registerDirty($this->insulin);
        $this->unitOfWork->commit();

        $this->unitOfWork->commit();

        self::assertSame(['5909998887776'], $this->medications->savedEans);
        self::assertSame(2, $this->transactions->commits);
    }
}
