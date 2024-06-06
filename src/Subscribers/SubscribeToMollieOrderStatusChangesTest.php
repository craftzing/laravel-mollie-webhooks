<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusChangedToAuthorized;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusChangedToCanceled;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusChangedToCompleted;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusChangedToExpired;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusChangedToPaid;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeOrder;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Orders\FakeOrderHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\HandleAssertions;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mollie\Api\Types\OrderStatus;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebhookClient\Models\WebhookCall;

final class SubscribeToMollieOrderStatusChangesTest extends IntegrationTestCase
{
    private FakeOrderHistory $fakeOrderHistory;

    #[Before]
    public function fakePaymentHistory(): void
    {
        $this->afterApplicationCreated(function (): void {
            $this->swap(OrderHistory::class, $this->fakeOrderHistory = new FakeOrderHistory());
        });
    }

    #[Test]
    public function itCanBeRegisteredAsAQueuedSubscriberForTheGenericOrderEvent(): void
    {
        $this->dontFakeEvents();

        Event::subscribe(SubscribeToMollieOrderStatusChanges::class);
        Event::dispatch(new MollieOrderWasUpdated($this->generateOrderId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === SubscribeToMollieOrderStatusChanges::class;
        });
    }

    public static function orderHistory(): Generator
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

    #[Test]
    #[DataProvider('orderHistory')]
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
            Event::assertNotDispatched(MollieOrderStatusChangedToPaid::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusChangedToPaid::class,
                new HandleAssertions(function (MollieOrderStatusChangedToPaid $event) use ($orderId): void {
                    $this->assertSame($orderId, $event->orderId);
                }),
            );
        }
    }

    #[Test]
    #[DataProvider('orderHistory')]
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
            Event::assertNotDispatched(MollieOrderStatusChangedToExpired::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusChangedToExpired::class,
                new HandleAssertions(function (MollieOrderStatusChangedToExpired $event) use ($orderId): void {
                    $this->assertSame($orderId, $event->orderId);
                }),
            );
        }
    }

    #[Test]
    #[DataProvider('orderHistory')]
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
            Event::assertNotDispatched(MollieOrderStatusChangedToAuthorized::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusChangedToAuthorized::class,
                new HandleAssertions(function (MollieOrderStatusChangedToAuthorized $event) use ($orderId): void {
                    $this->assertSame($orderId, $event->orderId);
                }),
            );
        }
    }

    #[Test]
    #[DataProvider('orderHistory')]
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
            Event::assertNotDispatched(MollieOrderStatusChangedToCanceled::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusChangedToCanceled::class,
                new HandleAssertions(function (MollieOrderStatusChangedToCanceled $event) use ($orderId): void {
                    $this->assertSame($orderId, $event->orderId);
                }),
            );
        }
    }

    #[Test]
    #[DataProvider('orderHistory')]
    public function itCanHandleWebhookCallsIndicatingAnOrderStatusChangedToCompleted(callable $addOrderHistory): void
    {
        $completed = OrderStatus::STATUS_COMPLETED;
        $latestStatusInOrderHistory = $addOrderHistory($this->fakeOrderHistory);
        $webhookCall = $this->webhookCallIndicatingOrderStatusChangedTo($completed);
        $orderId = OrderId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMollieOrderStatusChanges::class](
            new MollieOrderWasUpdated($orderId, $webhookCall),
        );

        if ($latestStatusInOrderHistory === $completed) {
            Event::assertNotDispatched(MollieOrderStatusChangedToCompleted::class);
        } else {
            Event::assertDispatched(
                MollieOrderStatusChangedToCompleted::class,
                new HandleAssertions(function (MollieOrderStatusChangedToCompleted $event) use ($orderId): void {
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

    public static function statusesThatDontFireEvents(): Generator
    {
        yield 'Call with status: `' . OrderStatus::STATUS_CREATED . '`' => [
            OrderStatus::STATUS_CREATED,
        ];

        yield 'Call with status: ' . OrderStatus::STATUS_PENDING . '`' => [
            OrderStatus::STATUS_PENDING,
        ];
    }

    #[Test]
    #[DataProvider('statusesThatDontFireEvents')]
    public function itCanHandleWebhookCallsWithoutAnOrderStatusThatWeListenTo(string $status): void
    {
        $webhookCall = $this->webhookCallIndicatingOrderStatusChangedTo($status);
        $orderId = OrderId::fromString($webhookCall->payload['id']);

        $this->app[SubscribeToMollieOrderStatusChanges::class](
            new MollieOrderWasUpdated($orderId, $webhookCall),
        );

        Event::assertNotDispatched(MollieOrderStatusChangedToAuthorized::class);
        Event::assertNotDispatched(MollieOrderStatusChangedToCanceled::class);
        Event::assertNotDispatched(MollieOrderStatusChangedToCompleted::class);
        Event::assertNotDispatched(MollieOrderStatusChangedToExpired::class);
        Event::assertNotDispatched(MollieOrderStatusChangedToPaid::class);
    }
}
