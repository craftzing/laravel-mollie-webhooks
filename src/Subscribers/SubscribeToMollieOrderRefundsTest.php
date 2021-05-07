<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeOrder;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Orders\FakeOrderHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookClient\Models\WebhookCall;

final class SubscribeToMollieOrderRefundsTest extends IntegrationTestCase
{
    private FakeOrderHistory $fakeOrderHistory;

    protected array $eventsToFake = FakeRefund::STATUS_EVENTS;

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

    public function orderHistory(): Generator
    {
        yield 'Order has no refunds' => [
            fn (): array => [],
        ];

        foreach (FakeRefund::STATUSES as $refundStatus) {
            yield "Order has a $refundStatus refund" => [
                fn (Application $app): array => [
                    FakeRefund::fake($app)->withStatus($refundStatus),
                ],
            ];
        }

        yield 'Order has multiple refunds' => [
            fn (Application $app): array => [
                FakeRefund::fake($app)->withStatus('pending'),
                FakeRefund::fake($app)->withStatus('pending'),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider orderHistory
     */
    public function itDispatchesRefundStatusEventsWhenTheStatusIsNotInTheOrderHistory(callable $resolveRefunds): void
    {
        /** @var \Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund[] $refunds */
        $refunds = $resolveRefunds($this->app);
        $order = FakeOrder::fake($this->app)->withRefunds(...$refunds);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($order->id())
            ->create();

        $this->app[SubscribeToMollieOrderRefunds::class](new MollieOrderWasUpdated($order->id(), $webhookCall));

        if (! $refunds) {
            Event::assertNothingDispatched();
        }

        foreach ($refunds as $refund) {
            Event::assertDispatched(
                $refund->statusEventClass(),
                fn (object $event): bool =>
                    $order->id()->value() === $event->resourceId->value()
                    && $refund->id()->value() === $event->refundId->value(),
            );
        }
    }

    /**
     * @test
     * @dataProvider orderHistory
     */
    public function itDoesNotDispatchRefundStatusEventsWhenTheStatusExistsInTheOrderHistory(
        callable $resolveRefunds
    ): void {
        /** @var \Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund[] $refunds */
        $refunds = $resolveRefunds($this->app);
        $order = FakeOrder::fake($this->app)->withRefunds(...$refunds);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($order->id())
            ->create();

        foreach ($refunds as $refund) {
            $this->fakeOrderHistory->fakeHasRefundWithStatusForOrder($order->id(), $refund->id(), $refund->status);
        }

        $this->app[SubscribeToMollieOrderRefunds::class](new MollieOrderWasUpdated($order->id(), $webhookCall));

        Event::assertNothingDispatched();
    }
}
