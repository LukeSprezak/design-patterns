<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

final readonly class PaymentResult
{
    public function __construct(
        public string $paymentId,
        public PaymentStatus $status,
    ) {
    }
}
