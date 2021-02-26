<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Payments;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\PrefixedResourceIdTestCase;

final class PaymentIdTest extends PrefixedResourceIdTestCase
{
    protected function resourceIdClass(): string
    {
        return PaymentId::class;
    }

    protected function expectedPrefix(): string
    {
        return 'tr_';
    }

    protected function expectedExceptionClass(): string
    {
        return InvalidResourceId::class;
    }
}
