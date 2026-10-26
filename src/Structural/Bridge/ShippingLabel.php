<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge;

abstract readonly class ShippingLabel
{
    public function __construct(
        protected Shipment $shipment,
        protected LabelPrinterLanguage $printerLanguage,
    ) {
    }

    #[\NoDiscard('the printer commands have to be sent to the printer')]
    final public function renderPrinterCommands(): string
    {
        return $this->printerLanguage->startLabel()
            . $this->renderContent()
            . $this->printerLanguage->endLabel();
    }

    abstract protected function renderContent(): string;

    protected function renderRoute(Address $from, Address $to): string
    {
        return $this->renderAddress(yInDots: 100, heading: 'FROM', address: $from)
            . $this->renderAddress(yInDots: 300, heading: 'TO', address: $to)
            . $this->printerLanguage->barcode(xInDots: 50, yInDots: 600, data: $this->shipment->trackingNumber);
    }

    private function renderAddress(int $yInDots, string $heading, Address $address): string
    {
        return $this->printerLanguage->text(50, $yInDots, $heading, FontSize::Normal)
            . $this->printerLanguage->text(50, $yInDots + 40, $address->name, FontSize::Normal)
            . $this->printerLanguage->text(50, $yInDots + 80, $address->street, FontSize::Normal)
            . $this->printerLanguage->text(50, $yInDots + 120, "{$address->postalCode} {$address->city}", FontSize::Normal);
    }
}
