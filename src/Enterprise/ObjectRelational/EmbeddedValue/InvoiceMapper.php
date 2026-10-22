<?php

declare(strict_types=1);

namespace DesignPatterns\Enterprise\ObjectRelational\EmbeddedValue;

use PDO;

/** Flattens Address and Money into columns of the invoice row and rebuilds them on the way back. */
final readonly class InvoiceMapper
{
    public function __construct(private PDO $connection)
    {
    }

    public function insert(Invoice $invoice): void
    {
        $this->connection
            ->prepare(<<<SQL
                INSERT INTO invoices (
                    number, recipient_street, recipient_postal_code, recipient_city, recipient_country_code,
                    net_amount_in_cents, vat_amount_in_cents, currency
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                SQL)
            ->execute([
                $invoice->number,
                $invoice->recipient->street,
                $invoice->recipient->postalCode,
                $invoice->recipient->city,
                $invoice->recipient->countryCode,
                $invoice->netAmount->amountInCents,
                $invoice->vatAmount->amountInCents,
                $invoice->netAmount->currency->value,
            ]);
    }

    public function find(string $number): ?Invoice
    {
        $statement = $this->connection->prepare('SELECT * FROM invoices WHERE number = ?');
        $statement->execute([$number]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $currency = Currency::from($row['currency']);

        return new Invoice(
            number: $row['number'],
            recipient: new Address(
                street: $row['recipient_street'],
                postalCode: $row['recipient_postal_code'],
                city: $row['recipient_city'],
                countryCode: $row['recipient_country_code'],
            ),
            netAmount: new Money($row['net_amount_in_cents'], $currency),
            vatAmount: new Money($row['vat_amount_in_cents'], $currency),
        );
    }
}
