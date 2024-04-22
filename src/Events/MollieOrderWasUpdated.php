<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Orders\OrderId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Illuminate\Queue\SerializesModels;
use Spatie\WebhookClient\Models\WebhookCall;

final class MollieOrderWasUpdated implements MollieResourceStatusWasUpdated
{
    use SerializesModels;

    public function __construct(
        /**
         * @readonly
         */
        public OrderId $orderId,
        /**
         * @readonly
         */
        public WebhookCall $webhookCall
    ) {
    }

    public function resourceId(): ResourceId
    {
        return $this->orderId;
    }

    public function webhookCall(): WebhookCall
    {
        return $this->webhookCall;
    }
}
