<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Illuminate\Queue\SerializesModels;
use Spatie\WebhookClient\Models\WebhookCall;

final class MolliePaymentWasUpdated implements MollieResourceStatusWasUpdated
{
    use SerializesModels;

    /**
     * @readonly
     */
    public PaymentId $paymentId;

    /**
     * @readonly
     */
    public WebhookCall $webhookCall;

    public function __construct(PaymentId $paymentId, WebhookCall $webhookCall)
    {
        $this->paymentId = $paymentId;
        $this->webhookCall = $webhookCall;
    }

    public function resourceId(): ResourceId
    {
        return $this->paymentId;
    }

    public function webhookCall(): WebhookCall
    {
        return $this->webhookCall;
    }
}
