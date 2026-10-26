<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

use DesignPatterns\Structural\Adapter\Vendor\Stripe\CardException;
use DesignPatterns\Structural\Adapter\Vendor\Stripe\InvalidRequestException;
use DesignPatterns\Structural\Adapter\Vendor\Stripe\StripeClient;

final readonly class StripePaymentGateway implements PaymentGateway
{
    public function __construct(
        private StripeClient $stripeClient,
    ) {
    }

    #[\Override]
    #[\NoDiscard('a pending or declined payment must not be treated as paid')]
    public function charge(string $orderNumber, int $amountInCents, string $paymentToken): PaymentResult
    {
        try {
            $paymentIntent = $this->stripeClient->createPaymentIntent([
                'amount' => $amountInCents,
                'currency' => 'pln',
                'payment_method' => $paymentToken,
                'confirm' => true,
                'metadata' => ['order_number' => $orderNumber],
            ]);
        } catch (CardException $cardException) {
            // A declined card is a regular business outcome, not an error the caller should have to catch.
            return new PaymentResult($cardException->paymentIntentId, PaymentStatus::Declined);
        }

        return new PaymentResult(
            paymentId: $paymentIntent->id,
            status: match ($paymentIntent->status) {
                'succeeded' => PaymentStatus::Paid,
                'processing', 'requires_action' => PaymentStatus::Pending,
            },
        );
    }

    #[\Override]
    public function refund(string $paymentId, int $amountInCents): void
    {
        try {
            $this->stripeClient->createRefund(['payment_intent' => $paymentId, 'amount' => $amountInCents]);
        } catch (InvalidRequestException $invalidRequestException) {
            throw new RefundRejectedException($invalidRequestException->getMessage(), previous: $invalidRequestException);
        }
    }
}
