<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Concerns;

use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Illuminate\Support\Str;
use function random_int;

trait FakesMollieData
{
    protected function paymentId(): PaymentId
    {
        return PaymentId::fromString(PaymentId::PREFIX . Str::random(random_int(4, 16)));
    }
}
