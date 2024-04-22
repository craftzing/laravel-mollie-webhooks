<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Orders;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\PrefixedResourceIdTestCase;

final class OrderIdTest extends PrefixedResourceIdTestCase
{
    protected static function resourceIdClass(): string
    {
        return OrderId::class;
    }

    protected static function expectedPrefix(): string
    {
        return 'ord_';
    }

    protected static function expectedExceptionClass(): string
    {
        return InvalidResourceId::class;
    }
}
