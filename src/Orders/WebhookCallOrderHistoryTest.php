<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;

use function array_merge;
use function json_encode;

final class WebhookCallOrderHistoryTest extends IntegrationTestCase
{
    public function orderWebhookCallHistory(): Generator
    {
        yield 'No webhook calls were made for the order so far' => [
            fn (): bool => false,
        ];

        yield 'Latest order status in the webhook call history differs from the one in the current webhook call' => [
            function (OrderId $orderId, string $status): bool {
                $latestStatus = $this->randomOrderStatusExcept($status);
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($orderId)
                    ->withOrderStatusInPayload($latestStatus)
                    ->create();

                return false;
            },
        ];

        yield 'Latest order status in the webhook call history matches from the one in the current webhook call' => [
            function (OrderId $orderId, string $status): bool {
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($orderId)
                    ->withOrderStatusInPayload($status)
                    ->create();

                return true;
            },
        ];

        yield 'Latest webhook call for the order did not include the status, but a webhook call before it does' => [
            function (OrderId $orderId, string $status): bool {
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($orderId)
                    ->withOrderStatusInPayload($status)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($orderId)
                    ->create();

                return true;
            },
        ];

        yield 'Latest webhook call for the order was due to a refund, but the latest status differs' => [
            function (OrderId $orderId, string $status): bool {
                $latestStatus = $this->randomOrderStatusExcept($status);
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($orderId)
                    ->withOrderStatusInPayload($latestStatus)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($orderId)
                    ->withRefundInPayload()
                    ->create();

                return false;
            },
        ];

        yield 'Latest webhook call for the order was due to a refund, but the latest status is the same' => [
            function (OrderId $orderId, string $status): bool {
                $secondALastWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($orderId)
                    ->withOrderStatusInPayload($status)
                    ->create();
                $latestWebhookCall = FakeMollieWebhookCall::new()
                    ->forResourceId($orderId)
                    ->withRefundInPayload()
                    ->create();

                return true;
            },
        ];
    }

    /**
     * @test
     * @dataProvider orderWebhookCallHistory
     */
    public function itCanCheckIfItHasALatestStatusForAnOrder(callable $resolveExpectedResult): void
    {
        $orderId = $this->generateOrderId();
        $status = $this->randomOrderStatusExcept();
        $expectedToHaveSameLatestStatus = $resolveExpectedResult($orderId, $status);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($orderId)
            ->create();

        $result = $this->app[WebhookCallOrderHistory::class]->hasLatestStatusForOrder(
            $orderId,
            $status,
            $webhookCall,
        );

        $this->assertSame($expectedToHaveSameLatestStatus, $result);
        $this->assertDatabaseHas(FakeMollieWebhookCall::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode(array_merge(
                ['id' => $orderId->value()],

                // Only when the OrderHistory is not expected to have the same latest status, we should
                // expect the freshly retrieved status to be persisted to the ongoing webhook call payload.
                ! $expectedToHaveSameLatestStatus ? ['order_status' => $status] : [],
            )),
        ]);
    }

    public function refundsWebhookCallHistory(): Generator
    {
        foreach (FakeRefund::STATUSES as $refundStatus) {
            yield "$refundStatus - No webhook calls were made for the order" => [
                fn (): string => $refundStatus,
                false,
            ];

            yield "$refundStatus - Order has no refunds in the webhook call history" => [
                function (OrderId $orderId) use ($refundStatus): string {
                    $latestWebhookCall = FakeMollieWebhookCall::new()
                        ->forResourceId($orderId)
                        ->create();

                    return $refundStatus;
                },
                false,
            ];

            yield "$refundStatus - Order has the refund with the same status in the webhook call history" => [
                function (OrderId $orderId, RefundId $refundId) use ($refundStatus): string {
                    $latestWebhookCall = FakeMollieWebhookCall::new()
                        ->forResourceId($orderId)
                        ->withRefundInPayload($refundId, $refundStatus)
                        ->create();

                    return $refundStatus;
                },
                true,
            ];

            yield "$refundStatus - Order has the refund with a different status in the webhook call history" => [
                function (OrderId $orderId, RefundId $refundId) use ($refundStatus): string {
                    $latestWebhookCall = FakeMollieWebhookCall::new()
                        ->forResourceId($orderId)
                        ->withRefundInPayload($refundId, $this->randomRefundStatusExcept($refundStatus))
                        ->create();

                    return $refundStatus;
                },
                false,
            ];

            yield "$refundStatus - Order has a different refund with the same status in the webhook call history" => [
                function (OrderId $orderId, RefundId $refundId) use ($refundStatus): string {
                    $latestWebhookCall = FakeMollieWebhookCall::new()
                        ->forResourceId($orderId)
                        ->withRefundInPayload(null, $refundStatus)
                        ->create();

                    return $refundStatus;
                },
                false,
            ];
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
        $this->assertDatabaseHas(FakeMollieWebhookCall::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode(array_merge(
                ['id' => $orderId->value()],

                // Only when the OrderHistory is not expected to have the transferred refund, we should expect
                // the freshly retrieved RefundId to be persisted to the ongoing webhook call payload.
                ! $expectedToHaveRefundWithStatus ? [
                    'refund' => [
                        'id' => $refundId->value(),
                        'refund_status' => $refundStatus,
                    ],
                ] : [],
            )),
        ]);
    }
}
