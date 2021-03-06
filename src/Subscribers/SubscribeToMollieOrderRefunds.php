<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Laravel\Wrappers\MollieApiWrapper;

final class SubscribeToMollieOrderRefunds implements ShouldQueue
{
    use DispatchesRefundEventsForResources;

    private OrderEndpoint $orders;
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

        /* @var \Mollie\Api\Resources\Refund $refund */
        foreach ($order->refunds() as $refund) {
            $refundId = RefundId::fromString($refund->id);
            $hasRefundWithStatusForPayment = $this->orderHistory->hasRefundWithStatusForOrder(
                $orderId,
                $refundId,
                $refund->status,
                $event->webhookCall,
            );

            if ($hasRefundWithStatusForPayment) {
                continue;
            }

            $this->dispatchRefundEvents($orderId, $refundId, $refund);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MollieOrderWasUpdated::class, self::class);
    }
}
