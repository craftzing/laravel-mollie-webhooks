<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;

final class MollieRefundWasTransferred
{
    /**
     * @readonly
     */
    public RefundId $refundId;

    /**
     * @readonly
     */
    public ResourceId $resourceId;

    private function __construct(RefundId $refundId, ResourceId $resourceId)
    {
        $this->refundId = $refundId;
        $this->resourceId = $resourceId;
    }

    public static function forPayment(PaymentId $paymentId, RefundId $refundId): self
    {
        return new self($refundId, $paymentId);
    }

    public static function forOrder(OrderId $orderId, RefundId $refundId): self
    {
        return new self($refundId, $orderId);
    }
}
