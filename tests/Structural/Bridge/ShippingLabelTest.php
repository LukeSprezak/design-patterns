<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Bridge;

use DesignPatterns\Structural\Bridge\Address;
use DesignPatterns\Structural\Bridge\Label\DangerousGoodsShippingLabel;
use DesignPatterns\Structural\Bridge\Label\ReturnShippingLabel;
use DesignPatterns\Structural\Bridge\Label\StandardShippingLabel;
use DesignPatterns\Structural\Bridge\LabelPrinterLanguage;
use DesignPatterns\Structural\Bridge\PrinterLanguage\EplPrinterLanguage;
use DesignPatterns\Structural\Bridge\PrinterLanguage\ZplPrinterLanguage;
use DesignPatterns\Structural\Bridge\Shipment;
use DesignPatterns\Structural\Bridge\ShippingLabel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShippingLabel::class)]
#[CoversClass(StandardShippingLabel::class)]
#[CoversClass(ReturnShippingLabel::class)]
#[CoversClass(DangerousGoodsShippingLabel::class)]
#[CoversClass(ZplPrinterLanguage::class)]
#[CoversClass(EplPrinterLanguage::class)]
final class ShippingLabelTest extends TestCase
{
    #[Test]
    #[DataProvider('standardLabelsInEveryPrinterLanguage')]
    public function standardLabelIsRenderedInPrinterLanguage(LabelPrinterLanguage $printerLanguage, string $expectedPrinterCommands): void
    {
        $printerCommands = new StandardShippingLabel(self::shipment(), $printerLanguage)->renderPrinterCommands();

        self::assertSame($expectedPrinterCommands, $printerCommands);
    }

    #[Test]
    #[DataProvider('printerLanguages')]
    public function returnLabelIsAddressedBackToSenderUnderBanner(LabelPrinterLanguage $printerLanguage): void
    {
        $printerCommands = new ReturnShippingLabel(self::shipment(), $printerLanguage)->renderPrinterCommands();

        self::assertStringContainsString('RETURN', $printerCommands);
        self::assertLessThan(
            strpos($printerCommands, 'Northwind Fulfillment'),
            strpos($printerCommands, 'Anna Nowak'),
        );
    }

    #[Test]
    #[DataProvider('dangerousGoodsMarkingsInEveryPrinterLanguage')]
    public function dangerousGoodsLabelShowsUnNumberInFrame(LabelPrinterLanguage $printerLanguage, string $expectedDangerousGoodsMarking): void
    {
        $printerCommands = new DangerousGoodsShippingLabel(self::shipment(), $printerLanguage, unNumber: 'UN1266')
            ->renderPrinterCommands();

        self::assertStringContainsString($expectedDangerousGoodsMarking, $printerCommands);
    }

    /** @return iterable<string, array{LabelPrinterLanguage, string}> */
    public static function standardLabelsInEveryPrinterLanguage(): iterable
    {
        yield 'ZPL' => [new ZplPrinterLanguage(), <<<'ZPL'
            ^XA
            ^FO50,100^A0N,30,30^FDFROM^FS
            ^FO50,140^A0N,30,30^FDNorthwind Fulfillment^FS
            ^FO50,180^A0N,30,30^FDul. Magazynowa 5^FS
            ^FO50,220^A0N,30,30^FD62-080 Tarnowo Podgorne^FS
            ^FO50,300^A0N,30,30^FDTO^FS
            ^FO50,340^A0N,30,30^FDAnna Nowak^FS
            ^FO50,380^A0N,30,30^FDul. Prosta 1^FS
            ^FO50,420^A0N,30,30^FD00-001 Warszawa^FS
            ^FO50,600^BCN,100,Y,N,N^FD00340434161094042557^FS
            ^XZ

            ZPL];
        yield 'EPL' => [new EplPrinterLanguage(), <<<'EPL'
            N
            A50,100,0,3,1,1,N,"FROM"
            A50,140,0,3,1,1,N,"Northwind Fulfillment"
            A50,180,0,3,1,1,N,"ul. Magazynowa 5"
            A50,220,0,3,1,1,N,"62-080 Tarnowo Podgorne"
            A50,300,0,3,1,1,N,"TO"
            A50,340,0,3,1,1,N,"Anna Nowak"
            A50,380,0,3,1,1,N,"ul. Prosta 1"
            A50,420,0,3,1,1,N,"00-001 Warszawa"
            B50,600,0,1,2,2,100,B,"00340434161094042557"
            P1

            EPL];
    }

    /** @return iterable<string, array{LabelPrinterLanguage}> */
    public static function printerLanguages(): iterable
    {
        yield 'ZPL' => [new ZplPrinterLanguage()];
        yield 'EPL' => [new EplPrinterLanguage()];
    }

    /** @return iterable<string, array{LabelPrinterLanguage, string}> */
    public static function dangerousGoodsMarkingsInEveryPrinterLanguage(): iterable
    {
        yield 'ZPL' => [new ZplPrinterLanguage(), "^FO50,800^GB300,120,6^FS\n^FO80,840^A0N,60,60^FDUN1266^FS\n"];
        yield 'EPL' => [new EplPrinterLanguage(), "X50,800,6,350,920\nA80,840,0,4,2,2,N,\"UN1266\"\n"];
    }

    private static function shipment(): Shipment
    {
        return new Shipment(
            trackingNumber: '00340434161094042557',
            sender: new Address(name: 'Northwind Fulfillment', street: 'ul. Magazynowa 5', postalCode: '62-080', city: 'Tarnowo Podgorne'),
            recipient: new Address(name: 'Anna Nowak', street: 'ul. Prosta 1', postalCode: '00-001', city: 'Warszawa'),
        );
    }
}
