<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Refunds;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\PrefixedResourceIdTestCase;

final class RefundIdTest extends PrefixedResourceIdTestCase
{
    protected function resourceIdClass(): string
    {
        return RefundId::class;
    }

    protected function expectedPrefix(): string
    {
        return 're_';
    }

    protected function expectedExceptionClass(): string
    {
        return InvalidResourceId::class;
    }
}
