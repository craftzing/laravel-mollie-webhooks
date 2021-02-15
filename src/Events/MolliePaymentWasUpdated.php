<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Events;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Illuminate\Queue\SerializesModels;
use Spatie\WebhookClient\Models\WebhookCall;

final class MolliePaymentWasUpdated
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
}
