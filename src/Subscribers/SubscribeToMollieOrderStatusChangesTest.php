<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToAuthorized;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToCanceled;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToExpired;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToPaid;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeOrder;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Orders\FakeOrderHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\Testing\TruthTest;
use Generator;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mollie\Api\Types\OrderStatus;
use Spatie\WebhookClient\Models\WebhookCall;

final class SubscribeToMollieOrderStatusChangesTest extends IntegrationTestCase
{
    private FakeOrderHistory $fakeOrderHistory;

    /**
     * @before
     */
    public function fakePaymentHistory(): void
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

        Event::subscribe(SubscribeToMollieOrderStatusChanges::class);
        Event::dispatch(new MollieOrderWasUpdated($this->generateOrderId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === SubscribeToMollieOrderStatusChanges::class;
        });
    }

    public function orderHistory(): Generator
    {
        yield 'Order history does not have status for the order yet' => [
            fn () => null,
        ];

        foreach (FakeOrder::STATUSES as $orderStatus) {
            yield "Order history has `$orderStatus` as the latest status for the order" => [
                function (FakeOrderHistory $fakeOrderHistory) use ($orderStatus): string {
                    $fakeOrderHistory->fakeLatestStatus($orderStatus);

                    return $orderStatus;
                },
            ];
        }
    }

    /**
     * @test
     * @dataProvider orderHistory
     */
    public function itCanHandleWebhookCallsIndicatingAnOrderStatusChangedToPaid(callable $addOrderHistory): void
    {
        $paid = OrderStatus::STATUS_PAID;
        $latestStatusInOrderHistory = $addOrderHistory($this->fakeOrderHistory);
        $webhookCall = $this->webhookCallIndicatingOrderStatusChangedTo($paid);
        $orderId = OrderId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMollieOrderStatusChanges::class](
            new MollieOrderWasUpdated($orderId, $webhookCall),
        );

        if ($latestStatusInOrderHistory === $paid) {
            Event::assertNotDispatched(MollieOrderStatusWasChangedToPaid::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusWasChangedToPaid::class,
                new TruthTest(function (MollieOrderStatusWasChangedToPaid $event) use ($orderId): void {
                    $this->assertSame($orderId, $event->orderId);
                }),
            );
        }
    }

    /**
     * @test
     * @dataProvider orderHistory
     */
    public function itCanHandleWebhookCallsIndicatingAnOrderStatusChangedToExpired(callable $addOrderHistory): void
    {
        $expired = OrderStatus::STATUS_EXPIRED;
        $latestStatusInOrderHistory = $addOrderHistory($this->fakeOrderHistory);
        $webhookCall = $this->webhookCallIndicatingOrderStatusChangedTo($expired);
        $orderId = OrderId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMollieOrderStatusChanges::class](
            new MollieOrderWasUpdated($orderId, $webhookCall),
        );

        if ($latestStatusInOrderHistory === $expired) {
            Event::assertNotDispatched(MollieOrderStatusWasChangedToExpired::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusWasChangedToExpired::class,
                new TruthTest(function (MollieOrderStatusWasChangedToExpired $event) use ($orderId): void {
                    $this->assertSame($orderId, $event->orderId);
                }),
            );
        }
    }

    /**
     * @test
     * @dataProvider orderHistory
     */
    public function itCanHandleWebhookCallsIndicatingAnOrderStatusChangedToAuthorized(callable $addOrderHistory): void
    {
        $authorized = OrderStatus::STATUS_AUTHORIZED;
        $latestStatusInOrderHistory = $addOrderHistory($this->fakeOrderHistory);
        $webhookCall = $this->webhookCallIndicatingOrderStatusChangedTo($authorized);
        $orderId = OrderId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMollieOrderStatusChanges::class](
            new MollieOrderWasUpdated($orderId, $webhookCall),
        );

        if ($latestStatusInOrderHistory === $authorized) {
            Event::assertNotDispatched(MollieOrderStatusWasChangedToAuthorized::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusWasChangedToAuthorized::class,
                new TruthTest(function (MollieOrderStatusWasChangedToAuthorized $event) use ($orderId): void {
                    $this->assertSame($orderId, $event->orderId);
                }),
            );
        }
    }

    /**
     * @test
     * @dataProvider orderHistory
     */
    public function itCanHandleWebhookCallsIndicatingAnOrderStatusChangedToCanceled(callable $addOrderHistory): void
    {
        $canceled = OrderStatus::STATUS_CANCELED;
        $latestStatusInOrderHistory = $addOrderHistory($this->fakeOrderHistory);
        $webhookCall = $this->webhookCallIndicatingOrderStatusChangedTo($canceled);
        $orderId = OrderId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMollieOrderStatusChanges::class](
            new MollieOrderWasUpdated($orderId, $webhookCall),
        );

        if ($latestStatusInOrderHistory === $canceled) {
            Event::assertNotDispatched(MollieOrderStatusWasChangedToCanceled::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusWasChangedToCanceled::class,
                new TruthTest(function (MollieOrderStatusWasChangedToCanceled $event) use ($orderId): void {
                    $this->assertSame($orderId, $event->orderId);
                }),
            );
        }
    }

    private function webhookCallIndicatingOrderStatusChangedTo(string $status): WebhookCall
    {
        $order = FakeOrder::fake($this->app)->withStatus($status);

        return FakeMollieWebhookCall::new()
            ->forResourceId($order->id())
            ->create();
    }
}
