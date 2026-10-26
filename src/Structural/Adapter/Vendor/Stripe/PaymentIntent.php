<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter\Vendor\Stripe;

final readonly class PaymentIntent
{
    public function __construct(
        public string $id,
        public string $status,
    ) {
    }
}
