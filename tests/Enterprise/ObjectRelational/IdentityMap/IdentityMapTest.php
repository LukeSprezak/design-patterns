<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\IdentityMap;

use DateTimeImmutable;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Medication\Medication;
use DesignPatterns\Enterprise\DomainLogic\DomainModel\Prescription\Prescription;
use DesignPatterns\Enterprise\ObjectRelational\IdentityMap\IdentityMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(IdentityMap::class)]
final class IdentityMapTest extends TestCase
{
    private IdentityMap $identityMap;

    protected function setUp(): void
    {
        $this->identityMap = new IdentityMap();
    }

    #[Test]
    public function returnsNullUntilObjectIsAddedThenAlwaysTheSameInstance(): void
    {
        $apap = new Medication(ean: '5901234123457', name: 'Apap 500 mg', priceInCents: 749, stock: 10);

        self::assertNull($this->identityMap->get(Medication::class, '5901234123457'));

        $this->identityMap->add('5901234123457', $apap);

        self::assertSame($apap, $this->identityMap->get(Medication::class, '5901234123457'));
    }

    #[Test]
    public function keysObjectsByClassSoEqualIdsOfDifferentClassesDoNotCollide(): void
    {
        $medication = new Medication(ean: '1', name: 'Apap 500 mg', priceInCents: 749, stock: 10);
        $prescription = new Prescription(id: 1, validUntil: new DateTimeImmutable('2026-11-15'), items: []);

        $this->identityMap->add('1', $medication);
        $this->identityMap->add(1, $prescription);

        self::assertSame($medication, $this->identityMap->get(Medication::class, '1'));
        self::assertSame($prescription, $this->identityMap->get(Prescription::class, 1));
    }
}
