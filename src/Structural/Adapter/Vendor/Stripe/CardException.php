<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter\Vendor\Stripe;

final class CardException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $paymentIntentId,
    ) {
        parent::__construct($message);
    }
}
