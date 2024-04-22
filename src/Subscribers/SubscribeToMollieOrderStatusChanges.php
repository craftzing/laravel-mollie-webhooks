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
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;

final class SubscribeToMollieOrderStatusChanges implements ShouldQueue
{
    private readonly OrderEndpoint $orders;

    public function __construct(
        MollieApiClient $mollie,
        private readonly Dispatcher $events,
        private readonly OrderHistory $orderHistory
    ) {
        $this->orders = $mollie->orders;
    }

    public function __invoke(MollieOrderWasUpdated $event): void
    {
        $orderId = $event->orderId;
        $order = $this->orders->get($orderId->value());

        if ($this->orderHistory->hasLatestStatusForOrder($orderId, $order->status, $event->webhookCall)) {
            return;
        }

        if ($order->status === OrderStatus::STATUS_PAID) {
            $this->events->dispatch(new MollieOrderStatusChangedToPaid($orderId));

            return;
        }

        if ($order->status === OrderStatus::STATUS_AUTHORIZED) {
            $this->events->dispatch(new MollieOrderStatusChangedToAuthorized($orderId));

            return;
        }

        if ($order->status === OrderStatus::STATUS_COMPLETED) {
            $this->events->dispatch(new MollieOrderStatusChangedToCompleted($orderId));

            return;
        }

        if ($order->status === OrderStatus::STATUS_EXPIRED) {
            $this->events->dispatch(new MollieOrderStatusChangedToExpired($orderId));

            return;
        }

        if ($order->status === OrderStatus::STATUS_CANCELED) {
            $this->events->dispatch(new MollieOrderStatusChangedToCanceled($orderId));

            return;
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MollieOrderWasUpdated::class, self::class);
    }
}
