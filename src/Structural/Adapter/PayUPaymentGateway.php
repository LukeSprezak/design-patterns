<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

use DesignPatterns\Structural\Adapter\Vendor\PayU\PayUClient;

final readonly class PayUPaymentGateway implements PaymentGateway
{
    public function __construct(
        private PayUClient $payUClient,
    ) {
    }

    #[\Override]
    #[\NoDiscard('a pending or declined payment must not be treated as paid')]
    public function charge(string $orderNumber, int $amountInCents, string $paymentToken): PaymentResult
    {
        $createdOrder = $this->payUClient->createOrder([
            'extOrderId' => $orderNumber,
            'totalAmount' => (string) $amountInCents,
            'currencyCode' => 'PLN',
            'payMethods' => ['payMethod' => ['type' => 'CARD_TOKEN', 'value' => $paymentToken]],
        ]);

        return new PaymentResult(
            paymentId: $createdOrder['orderId'],
            status: match ($createdOrder['orderStatus']) {
                'COMPLETED' => PaymentStatus::Paid,
                'PENDING', 'WAITING_FOR_CONFIRMATION' => PaymentStatus::Pending,
                'CANCELED' => PaymentStatus::Declined,
            },
        );
    }

    #[\Override]
    public function refund(string $paymentId, int $amountInCents): void
    {
        $refundResponse = $this->payUClient->createRefund($paymentId, [
            'refund' => ['description' => 'Order refund', 'amount' => (string) $amountInCents],
        ]);

        if ($refundResponse['status']['statusCode'] !== 'SUCCESS') {
            throw new RefundRejectedException($refundResponse['status']['statusDesc']);
        }
    }
}
