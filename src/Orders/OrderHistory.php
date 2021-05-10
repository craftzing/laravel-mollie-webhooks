<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Spatie\WebhookClient\Models\WebhookCall;

interface OrderHistory
{
    public function hasLatestStatusForOrder(
        OrderId $orderId,
        string $status,
        WebhookCall $ongoingWebhookCall
    ): bool;

    public function hasRefundWithStatusForOrder(
        OrderId $orderId,
        RefundId $refundId,
        string $refundStatus,
        WebhookCall $ongoingWebhookCall
    ): bool;
}
