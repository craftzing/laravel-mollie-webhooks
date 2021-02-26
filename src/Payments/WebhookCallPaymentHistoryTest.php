<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;
use Mollie\Api\Types\RefundStatus;

use function array_merge;
use function json_encode;

final class WebhookCallPaymentHistoryTest extends IntegrationTestCase
{
    public function paymentWebhookCallHistory(): Generator
    {
        yield 'No webhook calls were made for the payment so far' => [
            fn (): bool => false,
        ];

        yield 'Latest payment status in the webhook call history differs from the one in the current webhook call' => [
            function (PaymentId $paymentId, string $status): bool {
                $latestStatus = $this->randomPaymentStatusExcept($status);
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withPaymentStatusInPayload($latestStatus)
                    ->create();

                return false;
            },
        ];

        yield 'Latest payment status in the webhook call history matches from the one in the current webhook call' => [
            function (PaymentId $paymentId, string $status): bool {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withPaymentStatusInPayload($status)
                    ->create();

                return true;
            },
        ];

        yield 'Latest webhook call for the payment did not include the status, but a webhook call before it does' => [
            function (PaymentId $paymentId, string $status): bool {
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withPaymentStatusInPayload($status)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->create();

                return true;
            },
        ];

        yield 'Latest webhook call for the payment was due to a refund, but the latest status differs' => [
            function (PaymentId $paymentId, string $status): bool {
                $latestStatus = $this->randomPaymentStatusExcept($status);
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withPaymentStatusInPayload($latestStatus)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withRefundInPayload()
                    ->create();

                return false;
            },
        ];

        yield 'Latest webhook call for the payment was due to a refund, but the latest status is the same' => [
            function (PaymentId $paymentId, string $status): bool {
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withPaymentStatusInPayload($status)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withRefundInPayload()
                    ->create();

                return true;
            },
        ];
    }

    /**
     * @test
     * @dataProvider paymentWebhookCallHistory
     */
    public function itCanCheckIfItHasALatestStatusForAPayment(callable $resolveExpectedResult): void
    {
        $paymentId = $this->generatePaymentId();
        $status = $this->randomPaymentStatusExcept();
        $expectedToHaveSameLatestStatus = $resolveExpectedResult($paymentId, $status);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($paymentId)
            ->create();

        $result = $this->app[WebhookCallPaymentHistory::class]->hasLatestStatusForPayment(
            $paymentId,
            $status,
            $webhookCall,
        );

        $this->assertSame($expectedToHaveSameLatestStatus, $result);
        $this->assertDatabaseHas(FakeMollieWebhookCall::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode(array_merge(
                ['id' => $paymentId->value()],

                // Only when the PaymentHistory is not expected to have the same latest status, we should
                // expect the freshly retrieved status to be persisted to the ongoing webhook call payload.
                ! $expectedToHaveSameLatestStatus ? ['payment_status' => $status] : [],
            )),
        ]);
    }

    public function refundsWebhookCallHistory(): Generator
    {
        yield 'No webhook calls were made for the payment so far' => [
            fn (): bool => false,
        ];

        yield 'Payment has no refunds in the webhook call history' => [
            function (PaymentId $paymentId): bool {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->create();

                return false;
            },
        ];

        yield 'Payment has same transferred refund in the webhook call history' => [
            function (PaymentId $paymentId, RefundId $refundId): bool {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withRefundInPayload($refundId)
                    ->create();

                return true;
            },
        ];

        yield 'Payment has same refund with different status in the webhook call history' => [
            function (PaymentId $paymentId, RefundId $refundId): bool {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withRefundInPayload($refundId, $this->randomRefundStatusExcept(RefundStatus::STATUS_REFUNDED))
                    ->create();

                return false;
            },
        ];

        yield 'Payment has a different transferred refund in the webhook call history' => [
            function (PaymentId $paymentId, RefundId $refundId): bool {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($paymentId)
                    ->withRefundInPayload()
                    ->create();

                return false;
            },
        ];
    }

    /**
     * @test
     * @dataProvider refundsWebhookCallHistory
     */
    public function itCanCheckIfItHasATransferredRefundForAPayment(callable $resolveExpectedResult): void
    {
        $paymentId = $this->generatePaymentId();
        $refundId = $this->generateRefundId();
        $expectedToHaveTransferredRefund = $resolveExpectedResult($paymentId, $refundId);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($paymentId)
            ->create();

        $result = $this->app[WebhookCallPaymentHistory::class]->hasTransferredRefundForPayment(
            $paymentId,
            $refundId,
            $webhookCall,
        );

        $this->assertSame($expectedToHaveTransferredRefund, $result);
        $this->assertDatabaseHas(FakeMollieWebhookCall::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode(array_merge(
                ['id' => $paymentId->value()],

                // Only when the PaymentHistory is not expected to have the transferred refund, we should expect
                // the freshly retrieved RefundId to be persisted to the ongoing webhook call payload.
                ! $expectedToHaveTransferredRefund ? [
                    'refund' => [
                        'id' => $refundId->value(),
                        'refund_status' => RefundStatus::STATUS_REFUNDED,
                    ],
                ] : [],
            )),
        ]);
    }
}
