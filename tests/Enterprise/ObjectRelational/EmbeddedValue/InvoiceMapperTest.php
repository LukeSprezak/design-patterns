<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Enterprise\ObjectRelational\EmbeddedValue;

use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\Address;
use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\Currency;
use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\Invoice;
use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\InvoiceMapper;
use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\InvoiceSchema;
use DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue\Money;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvoiceMapper::class)]
#[CoversClass(InvoiceSchema::class)]
#[CoversClass(Invoice::class)]
#[CoversClass(Address::class)]
final class InvoiceMapperTest extends TestCase
{
    private PDO $connection;
    private InvoiceMapper $invoices;
    private Invoice $invoice;

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        InvoiceSchema::create($this->connection);
        $this->invoices = new InvoiceMapper($this->connection);
        $this->invoice = new Invoice(
            number: 'FV/2026/10/0042',
            recipient: new Address(street: 'Main Street 5', postalCode: '00-001', city: 'Warsaw', countryCode: 'PL'),
            netAmount: new Money(10000, Currency::Pln),
            vatAmount: new Money(2300, Currency::Pln),
        );
    }

    #[Test]
    public function valueObjectsAreFlattenedIntoColumnsOfTheInvoiceRow(): void
    {
        $this->invoices->insert($this->invoice);

        self::assertSame(
            [
                'number' => 'FV/2026/10/0042',
                'recipient_street' => 'Main Street 5',
                'recipient_postal_code' => '00-001',
                'recipient_city' => 'Warsaw',
                'recipient_country_code' => 'PL',
                'net_amount_in_cents' => 10000,
                'vat_amount_in_cents' => 2300,
                'currency' => 'PLN',
            ],
            $this->connection->query('SELECT number, recipient_street, recipient_postal_code, recipient_city, recipient_country_code, net_amount_in_cents, vat_amount_in_cents, currency FROM invoices')->fetch(PDO::FETCH_ASSOC),
        );
    }

    #[Test]
    public function findRebuildsTheValueObjectsFromTheColumns(): void
    {
        $this->invoices->insert($this->invoice);

        $loaded = $this->invoices->find('FV/2026/10/0042');

        self::assertEquals($this->invoice->recipient, $loaded->recipient);
        self::assertEquals(new Money(12300, Currency::Pln), $loaded->grossAmount);
        self::assertNull($this->invoices->find('FV/2026/10/9999'));
    }
}
