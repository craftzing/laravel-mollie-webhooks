<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;

final class MollieRefundStatusChangedToPending
{
    public function __construct(
        public readonly RefundId $refundId,
        public readonly ResourceId $resourceId,
    ) {}
}
