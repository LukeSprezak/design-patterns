<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Bridge\Label;

use DesignPatterns\Structural\Bridge\ShippingLabel;

final readonly class StandardShippingLabel extends ShippingLabel
{
    #[\Override]
    protected function renderContent(): string
    {
        return $this->renderRoute(from: $this->shipment->sender, to: $this->shipment->recipient);
    }
}
