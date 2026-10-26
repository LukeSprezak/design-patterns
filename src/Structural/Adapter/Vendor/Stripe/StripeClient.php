<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter\Vendor\Stripe;

/**
 * Simulates the vendor SDK in memory: it stands for third-party code we cannot change.
 * Payment method ids decide the outcome the way test cards do.
 */
final class StripeClient
{
    /** @var array<string, int> */
    private array $refundableAmountsByPaymentIntentId = [];

    /**
     * @param array{amount: int, currency: string, payment_method: string, confirm: bool, metadata: array<string, string>} $params
     * @throws CardException
     */
    public function createPaymentIntent(array $params): PaymentIntent
    {
        $paymentIntentId = 'pi_' . bin2hex(random_bytes(12));
        $status = match ($params['payment_method']) {
            'pm_card_chargeDeclined' => throw new CardException('Your card was declined.', $paymentIntentId),
            'pm_card_authenticationRequired' => 'requires_action',
            default => 'succeeded',
        };

        if ($status === 'succeeded') {
            $this->refundableAmountsByPaymentIntentId[$paymentIntentId] = $params['amount'];
        }

        return new PaymentIntent($paymentIntentId, $status);
    }

    /**
     * @param array{payment_intent: string, amount: int} $params
     * @throws InvalidRequestException
     */
    public function createRefund(array $params): void
    {
        if ($params['amount'] > $this->refundableAmountsByPaymentIntentId[$params['payment_intent']]) {
            throw new InvalidRequestException('Refund amount is greater than unrefunded amount on charge.');
        }

        $this->refundableAmountsByPaymentIntentId[$params['payment_intent']] -= $params['amount'];
    }
}
