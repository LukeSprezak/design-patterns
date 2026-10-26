<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

interface PaymentGateway
{
    public function charge(string $orderNumber, int $amountInCents, string $paymentToken): PaymentResult;

    /** @throws RefundRejectedException */
    public function refund(string $paymentId, int $amountInCents): void;
}
