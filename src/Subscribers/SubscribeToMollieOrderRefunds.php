<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MollieOrderWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\OrderEndpoint;
use Mollie\Laravel\Wrappers\MollieApiWrapper;
use Spatie\WebhookClient\Models\WebhookCall;

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
            $this->dispatchRefundEvents($orderId, $refund, $event->webhookCall);
        }
    }

    protected function hasRefundWithStatusForResource(
        OrderId $resourceId,
        RefundId $refundId,
        string $refundStatus,
        WebhookCall $ongoingWebhookCall
    ): bool {
        return $this->orderHistory->hasRefundWithStatusForOrder(
            $resourceId,
            $refundId,
            $refundStatus,
            $ongoingWebhookCall
        );
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MollieOrderWasUpdated::class, self::class);
    }
}
