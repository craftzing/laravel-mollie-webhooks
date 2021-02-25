<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Spatie\WebhookClient\Models\WebhookCall;

interface MollieResourceStatusWasUpdated
{
    public function resourceId(): ResourceId;

    public function webhookCall(): WebhookCall;
}