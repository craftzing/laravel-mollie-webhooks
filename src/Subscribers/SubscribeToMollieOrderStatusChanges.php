<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToAuthorized;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToCanceled;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToCompleted;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToExpired;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderStatusWasChangedToPaid;
use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\Types\OrderStatus;
use Mollie\Laravel\Wrappers\MollieApiWrapper;

final class SubscribeToMollieOrderStatusChanges implements ShouldQueue
{
    private OrderEndpoint $orders;
    private Dispatcher $events;
    private OrderHistory $orderHistory;

    public function __construct(MollieApiWrapper $mollie, Dispatcher $events, OrderHistory $orderHistory)
    {
        $this->orders = $mollie->orders();
        $this->events = $events;
        $this->orderHistory = $orderHistory;
    }

    public function __invoke(MollieOrderWasUpdated $event): void
    {
        $orderId = $event->orderId;
        $order = $this->orders->get($orderId->value());

        if ($this->orderHistory->hasLatestStatusForOrder($orderId, $order->status, $event->webhookCall)) {
            return;
        }

        if ($order->status === OrderStatus::STATUS_PAID) {
            $this->events->dispatch(new MollieOrderStatusWasChangedToPaid($orderId));

            return;
        }

        if ($order->status === OrderStatus::STATUS_AUTHORIZED) {
            $this->events->dispatch(new MollieOrderStatusWasChangedToAuthorized($orderId));

            return;
        }

        if ($order->status === OrderStatus::STATUS_COMPLETED) {
            $this->events->dispatch(new MollieOrderStatusWasChangedToCompleted($orderId));

            return;
        }

        if ($order->status === OrderStatus::STATUS_EXPIRED) {
            $this->events->dispatch(new MollieOrderStatusWasChangedToExpired($orderId));

            return;
        }

        if ($order->status === OrderStatus::STATUS_CANCELED) {
            $this->events->dispatch(new MollieOrderStatusWasChangedToCanceled($orderId));

            return;
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MollieOrderWasUpdated::class, self::class);
    }
}
