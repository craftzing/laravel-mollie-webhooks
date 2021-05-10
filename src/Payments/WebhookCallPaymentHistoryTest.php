<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\ProvidesResourceWebhookCallHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePayment;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;

final class WebhookCallPaymentHistoryTest extends IntegrationTestCase
{
    use ProvidesResourceWebhookCallHistory;

    public function paymentWebhookCallHistory(): Generator
    {
        foreach (FakePayment::STATUSES as $paymentStatus) {
            yield from $this->resourceWebhookCallHistory($paymentStatus, [$this, 'randomPaymentStatusExcept']);
        }
    }

    /**
     * @test
     * @dataProvider paymentWebhookCallHistory
     */
    public function itCanCheckIfItHasALatestStatusForAPayment(
        callable $resolveOrderStatus,
        bool $expectedToHaveSameLatestStatus
    ): void {
        $paymentId = $this->generatePaymentId();
        $status = $resolveOrderStatus($paymentId);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($paymentId)
            ->create();

        $result = $this->app[WebhookCallPaymentHistory::class]->hasLatestStatusForPayment(
            $paymentId,
            $status,
            $webhookCall,
        );

        $this->assertSame($expectedToHaveSameLatestStatus, $result);

        if ($expectedToHaveSameLatestStatus) {
            $this->assertDatabaseHasWebHookCallForResource($webhookCall, $paymentId);
        } else {
            // Only when the PaymentHistory is not expected to have the same latest status, we should
            // expect the freshly retrieved status to be persisted to the ongoing webhook call payload.
            $this->assertDatabaseHasWebhookCallForResourceWithStatus($webhookCall, $paymentId, $status);
        }
    }

    /**
     * @test
     * @dataProvider refundsWebhookCallHistory
     */
    public function itCanCheckIfItHasATransferredRefundForAPayment(
        callable $resolveRefundStatus,
        bool $expectedToHaveRefundWithStatus
    ): void {
        $paymentId = $this->generatePaymentId();
        $refundId = $this->generateRefundId();
        $refundStatus = $resolveRefundStatus($paymentId, $refundId);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($paymentId)
            ->create();

        $result = $this->app[WebhookCallPaymentHistory::class]->hasRefundWithStatusForPayment(
            $paymentId,
            $refundId,
            $refundStatus,
            $webhookCall,
        );

        $this->assertSame($expectedToHaveRefundWithStatus, $result);

        if ($expectedToHaveRefundWithStatus) {
            $this->assertDatabaseHasWebHookCallForResource($webhookCall, $paymentId);
        } else {
            // Only when the PaymentHistory is not expected to have the refund, we should expect the
            // freshly retrieved RefundId to be persisted to the ongoing webhook call payload.
            $this->assertDatabaseHasWebhookCallForResourceWithRefundStatus(
                $webhookCall,
                $paymentId,
                $refundId,
                $refundStatus,
            );
        }
    }
}
