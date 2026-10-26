<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge\Label;

use DesignPatterns\Structural\Bridge\FontSize;
use DesignPatterns\Structural\Bridge\ShippingLabel;

final readonly class ReturnShippingLabel extends ShippingLabel
{
    #[\Override]
    protected function renderContent(): string
    {
        return $this->printerLanguage->text(xInDots: 50, yInDots: 30, content: 'RETURN', fontSize: FontSize::Large)
            . $this->renderRoute(from: $this->shipment->recipient, to: $this->shipment->sender);
    }
}
