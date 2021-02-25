<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Commands;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidResourceId;
use Craftzing\Laravel\MollieWebhooks\Exceptions\UnexpectedWebhookPayload;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Illuminate\Contracts\Events\Dispatcher;
use Spatie\WebhookClient\ProcessWebhookJob;

final class ProcessMollieWebhook extends ProcessWebhookJob
{
    public function handle(Dispatcher $events): void
    {
        $payload = $this->webhookCall->getAttribute('payload') ?: [];
        $id = $payload['id'] ?? null;

        if (! $id) {
            throw UnexpectedWebhookPayload::missingObjectIdentifier();
        }

        try {
            $this->handlePaymentEvent(PaymentId::fromString($id), $events);

            return;
        } catch (InvalidResourceId $e) {
            // The ID is not a PaymentId, moving on to try and use it as a different Mollie resource ID.
        }

        throw UnexpectedWebhookPayload::objectIdentifierCannotBeMappedToAMollieResource();
    }

    private function handlePaymentEvent(PaymentId $paymentId, Dispatcher $events): void
    {
        $events->dispatch(new MolliePaymentWasUpdated($paymentId, $this->webhookCall));
    }
}
