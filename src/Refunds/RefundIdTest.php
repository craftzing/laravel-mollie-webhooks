<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Refunds;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\PrefixedResourceIdTestCase;

final class RefundIdTest extends PrefixedResourceIdTestCase
{
    protected static function resourceIdClass(): string
    {
        return RefundId::class;
    }

    protected static function expectedPrefix(): string
    {
        return 're_';
    }

    protected static function expectedExceptionClass(): string
    {
        return InvalidResourceId::class;
    }
}
