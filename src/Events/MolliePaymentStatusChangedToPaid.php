<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\PaymentId;

final class MolliePaymentStatusChangedToPaid
{
    use ExposesPaymentStatus;
}
