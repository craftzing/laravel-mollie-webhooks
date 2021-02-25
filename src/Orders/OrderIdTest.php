<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\PrefixedResourceIdTestCase;

final class OrderIdTest extends PrefixedResourceIdTestCase
{
    protected function resourceIdClass(): string
    {
        return OrderId::class;
    }

    protected function expectedPrefix(): string
    {
        return 'ord_';
    }

    protected function expectedExceptionClass(): string
    {
        return InvalidResourceId::class;
    }
}
