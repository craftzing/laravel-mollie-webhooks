<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundWasTransferred;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeOrder;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Orders\FakeOrderHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\Testing\TruthTest;
use Generator;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mollie\Api\Types\RefundStatus;
use Spatie\WebhookClient\Models\WebhookCall;

final class SubscribeToMollieOrderRefundsTest extends IntegrationTestCase
{
    private FakeOrderHistory $fakeOrderHistory;

    /**
     * @before
     */
    public function fakeOrderHistory(): void
    {
        $this->afterApplicationCreated(function (): void {
            $this->swap(OrderHistory::class, $this->fakeOrderHistory = new FakeOrderHistory());
        });
    }

    /**
     * @test
     */
    public function itCanBeRegisteredAsAQueuedSubscriberForTheGenericOrderEvent(): void
    {
        $this->dontFakeEvents();

        Event::subscribe(SubscribeToMollieOrderRefunds::class);
        Event::dispatch(new MollieOrderWasUpdated($this->generateOrderId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === SubscribeToMollieOrderRefunds::class;
        });
    }

    /**
     * @test
     */
    public function itCanHandleOrdersWithoutRefunds(): void
    {
        $order = FakeOrder::fake($this->app);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($order->id())
            ->create();

        $this->app[SubscribeToMollieOrderRefunds::class](new MollieOrderWasUpdated($order->id(), $webhookCall));

        Event::assertNotDispatched(MollieRefundWasTransferred::class);
    }

    public function nonTransferredRefund(): Generator
    {
        foreach (FakeRefund::STATUSES as $status) {
            if ($status !== RefundStatus::STATUS_REFUNDED) {
                yield "Refund status is `$status`" => [$status];
            }
        }
    }

    /**
     * @test
     * @dataProvider nonTransferredRefund
     */
    public function itCanHandleOrdersWithoutATransferredRefund(string $status): void
    {
        $refund = FakeRefund::fake($this->app)->withStatus($status);
        $order = FakeOrder::fake($this->app)->withRefund($refund);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($order->id())
            ->create();

        $this->app[SubscribeToMollieOrderRefunds::class](new MollieOrderWasUpdated($order->id(), $webhookCall));

        Event::assertNotDispatched(MollieRefundWasTransferred::class);
    }

    /**
     * @test
     */
    public function itCanHandleOrdersWithMultipleRefunds(): void
    {
        $notTransferredRefund = FakeRefund::fake($this->app)->notTransferred();
        $transferredRefund = FakeRefund::fake($this->app)->transferred();
        $order = FakeOrder::fake($this->app)
            ->withRefund($notTransferredRefund)
            ->withRefund($transferredRefund);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($order->id())
            ->create();

        $this->app[SubscribeToMollieOrderRefunds::class](new MollieOrderWasUpdated($order->id(), $webhookCall));

        Event::assertDispatchedTimes(MollieRefundWasTransferred::class);
        Event::assertDispatched(
            MollieRefundWasTransferred::class,
            new TruthTest(function (MollieRefundWasTransferred $event) use ($order, $transferredRefund): void {
                $this->assertEquals($order->id(), $event->resourceId);
                $this->assertEquals($transferredRefund->id(), $event->refundId);
            }),
        );
    }

    public function orderHistory(): Generator
    {
        yield 'Transferred refund exists order history' => [
            function (FakeOrderHistory $fakeOrderHistory): bool {
                $fakeOrderHistory->fakeHasTransferredRefundForOrder();

                return true;
            },
        ];

        yield 'Transferred refund does not exists order history' => [
            fn (): bool => false,
        ];
    }

    /**
     * @test
     * @dataProvider orderHistory
     */
    public function itCanHandleWebhookCallsIndicatingARefundWasTransferredForAnOrder(callable $addOrderHistory): void
    {
        $refund = FakeRefund::fake($this->app)->transferred();
        $order = FakeOrder::fake($this->app)->withRefund($refund);
        $hasTransferredRefundForPaymentInHistory = $addOrderHistory($this->fakeOrderHistory);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($order->id())
            ->create();

        $this->app[SubscribeToMollieOrderRefunds::class](new MollieOrderWasUpdated($order->id(), $webhookCall));

        if ($hasTransferredRefundForPaymentInHistory) {
            Event::assertNotDispatched(MollieRefundWasTransferred::class);
        } else {
            Event::assertDispatched(
                MollieRefundWasTransferred::class,
                new TruthTest(function (MollieRefundWasTransferred $event) use ($order, $refund): void {
                    $this->assertEquals($order->id(), $event->resourceId);
                    $this->assertEquals($refund->id(), $event->refundId);
                }),
            );
        }
    }
}
