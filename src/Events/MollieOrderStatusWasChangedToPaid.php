<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

final class MollieOrderStatusWasChangedToPaid
{
    use ExposesOrderId;
}
