<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\PersistsChangesToOngoingWebhookCallPayload;
use Craftzing\Laravel\MollieWebhooks\Queries\LatestMollieWebhookCallByResourceId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\WebhookPayloadFragment;
use Spatie\WebhookClient\Models\WebhookCall;

use function compact;

final class WebhookCallOrderHistory implements OrderHistory
{
    use PersistsChangesToOngoingWebhookCallPayload;

    private LatestMollieWebhookCallByResourceId $latestMollieWebhookCallByResourceId;

    public function __construct(LatestMollieWebhookCallByResourceId $latestMollieWebhookCallByResourceId)
    {
        $this->latestMollieWebhookCallByResourceId = $latestMollieWebhookCallByResourceId;
    }

    public function hasLatestStatusForOrder(OrderId $orderId, string $status, WebhookCall $ongoingWebhookCall): bool
    {
        $latestWebhookCall = $this->latestMollieWebhookCallByResourceId->find(
            $orderId,
            $ongoingWebhookCall,
            WebhookPayloadFragment::fromValues(['order_status' => $status]),
        );

        // When the latest status for the order in the webhook call history DOES match the freshly
        // retrieved status, we should assume that the ongoing webhook call wasn't triggered due
        // to an order status change. Hence, we shouldn't persist it to the payload.
        if ($latestWebhookCall) {
            return true;
        }

        // When we couldn't find a previous webhook call for the order having a status in the payload, we should
        // assume that the ongoing webhook call was triggered due to an order status change. Therefore, we
        // should persist the freshly retrieved status to the payload of the ongoing webhook call in
        // order to have it as the latest status for that order for future webhook calls.
        $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, ['order_status' => $status]);

        return false;
    }

    public function hasRefundWithStatusForOrder(
        OrderId $orderId,
        RefundId $refundId,
        string $refundStatus,
        WebhookCall $ongoingWebhookCall
    ): bool {
        $refund = [
            'id' => $refundId->value(),
            'refund_status' => $refundStatus,
        ];
        $latestWebhookCall = $this->latestMollieWebhookCallByResourceId->find(
            $orderId,
            $ongoingWebhookCall,
            WebhookPayloadFragment::fromValues($refund),
        );

        // When the webhook call history has the settled refund for the order, we should
        // assume that the ongoing webhook call was not triggered due to an order
        // refund transfer. Hence, we shouldn't persist it to the payload.
        if ($latestWebhookCall) {
            return true;
        }

        // When we couldn't find a previous webhook call for the order having the refund in the payload, we should
        // assume that the ongoing webhook call was triggered due to an order refund transfer. Therefore, we
        // should persist the freshly retrieved refund to the payload of the ongoing webhook call in
        // order to have it as the settled refund for that order for future webhook calls.
        $this->persistChangeToOngoingWebhookCallPayload($ongoingWebhookCall, compact('refund'));

        return false;
    }
}
