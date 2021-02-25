<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\HasIdPrefix;
use Craftzing\Laravel\MollieWebhooks\ResourceId;

final class PaymentId implements ResourceId
{
    use HasIdPrefix;

    public const PREFIX = 'tr_';

    protected function prefix(): string
    {
        return self::PREFIX;
    }
}
