<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;

final class MollieOrderStatusChangedToPaid
{
    public function __construct(
        public readonly OrderId $orderId,
    ) {}
}
