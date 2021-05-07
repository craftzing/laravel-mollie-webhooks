<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Orders;

use Craftzing\Laravel\MollieWebhooks\Orders\OrderHistory;
use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Spatie\WebhookClient\Models\WebhookCall;

final class FakeOrderHistory implements OrderHistory
{
    private ?string $latestStatus = null;
    private array $knownRefundsWithStatus = [];

    public function hasLatestStatusForOrder(OrderId $orderId, string $status, WebhookCall $ongoingWebhookCall): bool
    {
        return $this->latestStatus === $status;
    }

    public function fakeLatestStatus(string $status): void
    {
        $this->latestStatus = $status;
    }

    public function hasRefundWithStatusForOrder(
        OrderId $orderId,
        RefundId $refundId,
        string $refundStatus,
        WebhookCall $ongoingWebhookCall
    ): bool {
        return $this->knownRefundsWithStatus[$this->serializeRefundStatus($orderId, $refundId, $refundStatus)] ?? false;
    }

    public function fakeHasRefundWithStatusForOrder(OrderId $orderId, RefundId $refundId, string $refundStatus): void
    {
        $this->knownRefundsWithStatus[$this->serializeRefundStatus($orderId, $refundId, $refundStatus)] = true;
    }

    private function serializeRefundStatus(OrderId $orderId, RefundId $refundId, string $refundStatus): string
    {
        return "$orderId.$refundId.$refundStatus";
    }
}
