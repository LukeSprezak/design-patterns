<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Creational\Singleton;

use DesignPatterns\Creational\Singleton\FiscalPrinterConnection;
use Error;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FiscalPrinterConnection::class)]
final class FiscalPrinterConnectionTest extends TestCase
{
    #[Test]
    public function returnsTheSameConnectionToEveryCaller(): void
    {
        $checkoutConnection = FiscalPrinterConnection::getInstance();
        $refundDeskConnection = FiscalPrinterConnection::getInstance();

        self::assertSame($checkoutConnection, $refundDeskConnection);
    }

    #[Test]
    public function receiptNumberingContinuesAcrossIndependentCallers(): void
    {
        $checkoutReceiptNumber = FiscalPrinterConnection::getInstance()
            ->printReceipt(['Ibuprofen 400 mg x1 7.49']);
        $refundDeskReceiptNumber = FiscalPrinterConnection::getInstance()
            ->printReceipt(['Refund: Ibuprofen 400 mg x1 -7.49']);

        self::assertSame($checkoutReceiptNumber + 1, $refundDeskReceiptNumber);
    }

    #[Test]
    public function keepsOneJournalOfEverythingPrinted(): void
    {
        $receiptNumber = FiscalPrinterConnection::getInstance()->printReceipt(['Apap 500 mg x2 14.98']);

        self::assertSame(['Apap 500 mg x2 14.98'], FiscalPrinterConnection::getInstance()->journal()[$receiptNumber]);
    }

    #[Test]
    public function cannotBeInstantiatedDirectly(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('private');

        new FiscalPrinterConnection();
    }

    #[Test]
    public function cannotBeCloned(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('private');

        clone FiscalPrinterConnection::getInstance();
    }

    #[Test]
    public function cannotBeRestoredFromSerializedForm(): void
    {
        $this->expectException(LogicException::class);

        unserialize(serialize(FiscalPrinterConnection::getInstance()));
    }
}
