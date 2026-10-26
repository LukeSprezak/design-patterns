<?php

declare(strict_types=1);

namespace DesignPatterns\Tests\Structural\Adapter;

use DesignPatterns\Structural\Adapter\PaymentGateway;
use DesignPatterns\Structural\Adapter\PaymentStatus;
use DesignPatterns\Structural\Adapter\PayUPaymentGateway;
use DesignPatterns\Structural\Adapter\RefundRejectedException;
use DesignPatterns\Structural\Adapter\StripePaymentGateway;
use DesignPatterns\Structural\Adapter\Vendor\PayU\PayUClient;
use DesignPatterns\Structural\Adapter\Vendor\Stripe\StripeClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PayUPaymentGateway::class)]
#[CoversClass(StripePaymentGateway::class)]
final class PaymentGatewayTest extends TestCase
{
    #[Test]
    #[DataProvider('cardOutcomes')]
    public function chargeTranslatesVendorOutcomeIntoPaymentStatus(
        PaymentGateway $paymentGateway,
        string $paymentToken,
        PaymentStatus $expectedPaymentStatus,
    ): void {
        $paymentResult = $paymentGateway->charge(orderNumber: 'ORD-2026-0001', amountInCents: 4999, paymentToken: $paymentToken);

        self::assertSame($expectedPaymentStatus, $paymentResult->status);
        self::assertNotSame('', $paymentResult->paymentId);
    }

    #[Test]
    #[DataProvider('gatewaysWithApprovedCard')]
    public function partialRefundsUpToChargedAmountAreAccepted(PaymentGateway $paymentGateway, string $approvedCardToken): void
    {
        $paymentResult = $paymentGateway->charge(orderNumber: 'ORD-2026-0002', amountInCents: 4999, paymentToken: $approvedCardToken);

        $this->expectNotToPerformAssertions();

        $paymentGateway->refund($paymentResult->paymentId, 2000);
        $paymentGateway->refund($paymentResult->paymentId, 2999);
    }

    #[Test]
    #[DataProvider('gatewaysWithApprovedCard')]
    public function refundExceedingRemainingAmountIsRejected(PaymentGateway $paymentGateway, string $approvedCardToken): void
    {
        $paymentResult = $paymentGateway->charge(orderNumber: 'ORD-2026-0003', amountInCents: 4999, paymentToken: $approvedCardToken);
        $paymentGateway->refund($paymentResult->paymentId, 4999);

        $this->expectException(RefundRejectedException::class);

        $paymentGateway->refund($paymentResult->paymentId, 1);
    }

    /** @return iterable<string, array{PaymentGateway, string, PaymentStatus}> */
    public static function cardOutcomes(): iterable
    {
        yield 'PayU, approved card' => [new PayUPaymentGateway(new PayUClient()), 'TOKC_APPROVED', PaymentStatus::Paid];
        yield 'PayU, card requiring 3-D Secure' => [new PayUPaymentGateway(new PayUClient()), 'TOKC_3DS', PaymentStatus::Pending];
        yield 'PayU, declined card' => [new PayUPaymentGateway(new PayUClient()), 'TOKC_DECLINED', PaymentStatus::Declined];
        yield 'Stripe, approved card' => [new StripePaymentGateway(new StripeClient()), 'pm_card_visa', PaymentStatus::Paid];
        yield 'Stripe, card requiring 3-D Secure' => [new StripePaymentGateway(new StripeClient()), 'pm_card_authenticationRequired', PaymentStatus::Pending];
        yield 'Stripe, declined card' => [new StripePaymentGateway(new StripeClient()), 'pm_card_chargeDeclined', PaymentStatus::Declined];
    }

    /** @return iterable<string, array{PaymentGateway, string}> */
    public static function gatewaysWithApprovedCard(): iterable
    {
        yield 'PayU' => [new PayUPaymentGateway(new PayUClient()), 'TOKC_APPROVED'];
        yield 'Stripe' => [new StripePaymentGateway(new StripeClient()), 'pm_card_visa'];
    }
}
