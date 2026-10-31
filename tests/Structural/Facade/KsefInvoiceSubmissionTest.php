<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Facade;

use DesignPatterns\Structural\Facade\Ksef\InvoiceEncryptor;
use DesignPatterns\Structural\Facade\Ksef\KsefAuthenticationClient;
use DesignPatterns\Structural\Facade\Ksef\KsefInvoiceClient;
use DesignPatterns\Structural\Facade\Ksef\KsefSessionClient;
use DesignPatterns\Structural\Facade\KsefInvoiceSubmission;
use DesignPatterns\Tests\Structural\Facade\Fake\InMemoryKsefServer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(KsefInvoiceSubmission::class)]
#[CoversClass(KsefAuthenticationClient::class)]
#[CoversClass(KsefSessionClient::class)]
#[CoversClass(KsefInvoiceClient::class)]
#[CoversClass(InvoiceEncryptor::class)]
final class KsefInvoiceSubmissionTest extends TestCase
{
    private const string SELLER_TAX_ID = '5265877635';
    private const string KSEF_TOKEN = '20261030-EC-2B5A1C9D4E-7F3B6A8C21-AB';
    private const string INVOICE_XML = '<Faktura><Fa><P_2>INV/2026/10/0001</P_2></Fa></Faktura>';

    #[Test]
    public function submittedInvoiceReceivesKsefNumberOfSeller(): void
    {
        $ksefServer = new InMemoryKsefServer(self::SELLER_TAX_ID, self::KSEF_TOKEN);

        $ksefNumber = self::ksefInvoiceSubmission($ksefServer)->submit(self::INVOICE_XML);

        self::assertMatchesRegularExpression('/^5265877635-\d{8}-[0-9A-F]{12}-[0-9A-F]{2}$/', $ksefNumber);
    }

    #[Test]
    public function invoiceReachesKsefEncryptedWithSessionKey(): void
    {
        $ksefServer = new InMemoryKsefServer(self::SELLER_TAX_ID, self::KSEF_TOKEN);

        (void) self::ksefInvoiceSubmission($ksefServer)->submit(self::INVOICE_XML);

        self::assertSame([self::INVOICE_XML], $ksefServer->decryptedInvoiceXmls);
    }

    #[Test]
    public function sessionIsClosedAfterSubmission(): void
    {
        $ksefServer = new InMemoryKsefServer(self::SELLER_TAX_ID, self::KSEF_TOKEN);

        (void) self::ksefInvoiceSubmission($ksefServer)->submit(self::INVOICE_XML);

        self::assertSame(0, $ksefServer->numberOfOpenSessions());
    }

    private static function ksefInvoiceSubmission(InMemoryKsefServer $ksefServer): KsefInvoiceSubmission
    {
        return new KsefInvoiceSubmission(
            authenticationClient: new KsefAuthenticationClient($ksefServer),
            invoiceEncryptor: new InvoiceEncryptor(),
            sessionClient: new KsefSessionClient($ksefServer),
            invoiceClient: new KsefInvoiceClient($ksefServer),
            sellerTaxId: self::SELLER_TAX_ID,
            ksefToken: self::KSEF_TOKEN,
        );
    }
}
