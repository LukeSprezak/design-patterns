<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter\Vendor\PayU;

/**
 * Simulates the vendor SDK in memory: it stands for third-party code we cannot change.
 * Card tokens decide the outcome the way sandbox test cards do.
 */
final class PayUClient
{
    /** @var array<string, int> */
    private array $refundableAmountsByOrderId = [];

    /**
     * @param array{extOrderId: string, totalAmount: string, currencyCode: string, payMethods: array{payMethod: array{type: string, value: string}}} $orderData
     * @return array{orderId: string, orderStatus: string}
     */
    public function createOrder(array $orderData): array
    {
        $orderId = strtoupper(bin2hex(random_bytes(10)));
        $orderStatus = match ($orderData['payMethods']['payMethod']['value']) {
            'TOKC_DECLINED' => 'CANCELED',
            'TOKC_3DS' => 'WAITING_FOR_CONFIRMATION',
            default => 'COMPLETED',
        };

        if ($orderStatus === 'COMPLETED') {
            $this->refundableAmountsByOrderId[$orderId] = (int) $orderData['totalAmount'];
        }

        return ['orderId' => $orderId, 'orderStatus' => $orderStatus];
    }

    /**
     * @param array{refund: array{description: string, amount: string}} $refundData
     * @return array{status: array{statusCode: string, statusDesc?: string}}
     */
    public function createRefund(string $orderId, array $refundData): array
    {
        $refundAmount = (int) $refundData['refund']['amount'];

        if ($refundAmount > $this->refundableAmountsByOrderId[$orderId]) {
            return ['status' => ['statusCode' => 'ERROR_VALUE_INVALID', 'statusDesc' => 'Refund amount exceeds the remaining order amount']];
        }

        $this->refundableAmountsByOrderId[$orderId] -= $refundAmount;

        return ['status' => ['statusCode' => 'SUCCESS']];
    }
}
