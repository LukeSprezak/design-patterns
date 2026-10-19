<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\IdentityMap;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DataSource\DataMapper\MedicationMapper as PlainMedicationMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\PdoTransactionManager;
use DesignPatterns\Enterprise\DataSource\DataMapper\PrescriptionMapper;
use DesignPatterns\Enterprise\DataSource\DataMapper\SaleMapper;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\RefundLevel;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\MedicationRepository;
use DesignPatterns\Enterprise\DomainLogic\ServiceLayer\PharmacyService;
use DesignPatterns\Enterprise\DomainLogic\TransactionScript\PharmacySchema;
use DesignPatterns\Enterprise\ObjectRelational\IdentityMap\IdentityMap;
use DesignPatterns\Enterprise\ObjectRelational\IdentityMap\MedicationMapper;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MedicationMapper::class)]
final class MedicationMapperTest extends TestCase
{
    private const string INSULIN_EAN = '5909998887776';

    private PDO $connection;
    private MedicationMapper $medications;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        PharmacySchema::create($this->connection);

        $this->connection->exec(<<<SQL
            INSERT INTO medications (ean, name, price_in_cents, stock) VALUES
                ('5901234123457', 'Apap 500 mg', 749, 10),
                ('5909998887776', 'Insulin Lantus', 12000, 3);
            INSERT INTO prescriptions (id, valid_until, dispensed_at) VALUES (1, '2026-11-15', NULL);
            INSERT INTO prescription_items (prescription_id, ean, quantity, refund_level) VALUES
                (1, '5909998887776', 1, 'R'),
                (1, '5909998887776', 1, '30');
            SQL);
        $this->medications = new MedicationMapper($this->connection, new IdentityMap());
    }

    #[Test]
    public function returnsTheSameObjectForTheSameEanWithinOneMap(): void
    {
        $firstLoad = $this->medications->get(self::INSULIN_EAN);
        $secondLoad = $this->medications->get(self::INSULIN_EAN);

        self::assertSame($firstLoad, $secondLoad);
        self::assertNotSame($firstLoad, $this->medications->get('5901234123457'));
    }

    #[Test]
    public function aFreshMapLoadsAFreshObjectFromTheDatabase(): void
    {
        $this->medications->get(self::INSULIN_EAN)->dispense(2, RefundLevel::Full);

        $reloaded = new MedicationMapper($this->connection, new IdentityMap())->get(self::INSULIN_EAN);

        self::assertSame(3, $reloaded->stock);
    }

    #[Test]
    public function prescriptionWithTheSameMedicationOnTwoItemsReducesStockByBothDoses(): void
    {
        (void) $this->pharmacyService($this->medications)->dispensePrescription(1, new DateTimeImmutable('2026-10-19'));

        self::assertSame(1, $this->insulinStockInDatabase());
    }

    #[Test]
    public function withoutIdentityMapTheSecondItemOverwritesTheFirstItemsStockUpdate(): void
    {
        (void) $this->pharmacyService(new PlainMedicationMapper($this->connection))->dispensePrescription(1, new DateTimeImmutable('2026-10-19'));

        self::assertSame(2, $this->insulinStockInDatabase());
    }

    private function pharmacyService(MedicationRepository $medications): PharmacyService
    {
        return new PharmacyService(
            new PrescriptionMapper($this->connection, $medications),
            $medications,
            new SaleMapper($this->connection),
            new PdoTransactionManager($this->connection),
        );
    }

    private function insulinStockInDatabase(): int
    {
        return (int) $this->connection->query("SELECT stock FROM medications WHERE ean = '5909998887776'")->fetchColumn();
    }
}
