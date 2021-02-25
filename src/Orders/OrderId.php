<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\HasIdPrefix;
use Craftzing\Laravel\MollieWebhooks\ResourceId;

final class OrderId implements ResourceId
{
    use HasIdPrefix;

    public const PREFIX = 'ord_';

    protected function prefix(): string
    {
        return self::PREFIX;
    }
}
