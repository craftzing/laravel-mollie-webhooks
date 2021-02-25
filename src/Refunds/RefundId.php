<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Refunds;

use Craftzing\Laravel\MollieWebhooks\HasIdPrefix;
use Craftzing\Laravel\MollieWebhooks\ResourceId;

final class RefundId implements ResourceId
{
    use HasIdPrefix;

    public const PREFIX = 're_';

    protected function prefix(): string
    {
        return self::PREFIX;
    }
}
