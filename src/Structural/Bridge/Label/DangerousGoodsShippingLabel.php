<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge\Label;

use DesignPatterns\Structural\Bridge\FontSize;
use DesignPatterns\Structural\Bridge\LabelPrinterLanguage;
use DesignPatterns\Structural\Bridge\Shipment;
use DesignPatterns\Structural\Bridge\ShippingLabel;

final readonly class DangerousGoodsShippingLabel extends ShippingLabel
{
    public function __construct(
        Shipment $shipment,
        LabelPrinterLanguage $printerLanguage,
        private string $unNumber,
    ) {
        parent::__construct($shipment, $printerLanguage);
    }

    #[\Override]
    protected function renderContent(): string
    {
        return $this->renderRoute(from: $this->shipment->sender, to: $this->shipment->recipient)
            . $this->printerLanguage->box(xInDots: 50, yInDots: 800, widthInDots: 300, heightInDots: 120, lineThicknessInDots: 6)
            . $this->printerLanguage->text(xInDots: 80, yInDots: 840, content: $this->unNumber, fontSize: FontSize::Large);
    }
}
