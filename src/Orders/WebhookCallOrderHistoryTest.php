<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\Testing\Concerns\ProvidesResourceWebhookCallHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeOrder;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;

final class WebhookCallOrderHistoryTest extends IntegrationTestCase
{
    use ProvidesResourceWebhookCallHistory;

    public function orderWebhookCallHistory(): Generator
    {
        foreach (FakeOrder::STATUSES as $orderStatus) {
            yield from $this->resourceWebhookCallHistory($orderStatus, [$this, 'randomOrderStatusExcept']);
        }
    }

    /**
     * @test
     * @dataProvider orderWebhookCallHistory
     */
    public function itCanCheckIfItHasALatestStatusForAnOrder(
        callable $resolveOrderStatus,
        bool $expectedToHaveSameLatestStatus
    ): void {
        $orderId = $this->generateOrderId();
        $status = $resolveOrderStatus($orderId);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($orderId)
            ->create();

        $result = $this->app[WebhookCallOrderHistory::class]->hasLatestStatusForOrder($orderId, $status, $webhookCall);

        $this->assertSame($expectedToHaveSameLatestStatus, $result);

        if ($expectedToHaveSameLatestStatus) {
            $this->assertDatabaseHasWebHookCallForResource($webhookCall, $orderId);
        } else {
            // Only when the OrderHistory is not expected to have the same latest status, we should
            // expect the freshly retrieved status to be persisted to the ongoing webhook call payload.
            $this->assertDatabaseHasWebhookCallForResourceWithStatus($webhookCall, $orderId, $status);
        }
    }

    /**
     * @test
     * @dataProvider refundsWebhookCallHistory
     */
    public function itCanCheckIfItHasARefundWithAStatusForAnOrder(
        callable $resolveRefundStatus,
        bool $expectedToHaveRefundWithStatus
    ): void {
        $orderId = $this->generateOrderId();
        $refundId = $this->generateRefundId();
        $refundStatus = $resolveRefundStatus($orderId, $refundId);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($orderId)
            ->create();

        $result = $this->app[WebhookCallOrderHistory::class]->hasRefundWithStatusForOrder(
            $orderId,
            $refundId,
            $refundStatus,
            $webhookCall,
        );

        $this->assertSame($expectedToHaveRefundWithStatus, $result);

        if ($expectedToHaveRefundWithStatus) {
            $this->assertDatabaseHasWebHookCallForResource($webhookCall, $orderId);
        } else {
            // Only when the OrderHistory is not expected to have the refund, we should expect the
            // freshly retrieved RefundId to be persisted to the ongoing webhook call payload.
            $this->assertDatabaseHasWebhookCallForResourceWithRefundStatus(
                $webhookCall,
                $orderId,
                $refundId,
                $refundStatus,
            );
        }
    }
}
