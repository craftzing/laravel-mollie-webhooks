<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;

trait ExposesRefundId
{
    /**
     * @readonly
     */
    public RefundId $refundId;

    /**
     * @readonly
     */
    public ResourceId $resourceId;

    public function __construct(RefundId $refundId, ResourceId $resourceId)
    {
        $this->refundId = $refundId;
        $this->resourceId = $resourceId;
    }
}
