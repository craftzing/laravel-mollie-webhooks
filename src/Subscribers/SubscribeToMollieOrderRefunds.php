<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Refund;

final class SubscribeToMollieOrderRefunds implements ShouldQueue
{
    use DispatchesRefundEventsForResources;

    private OrderEndpoint $orders;

    public function __construct(
        MollieApiClient $mollie,
        Dispatcher $events,
        private readonly OrderHistory $orderHistory,
    ) {
        $this->orders = $mollie->orders;
        $this->events = $events;
    }

    public function __invoke(MollieOrderWasUpdated $event): void
    {
        $orderId = $event->orderId;
        $order = $this->orders->get($orderId->value());

        /* @var Refund $refund */
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
