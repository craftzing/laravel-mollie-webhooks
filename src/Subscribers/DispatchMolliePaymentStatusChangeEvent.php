<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\CustomerHasCompletedPaymentOnMollie;
use Craftzing\Laravel\MollieWebhooks\Events\PaymentWasUpdatedOnMollie;
use Craftzing\Laravel\MollieWebhooks\Queries\MostRecentMollieWebhookCallByPayloadFragment;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Laravel\Wrappers\MollieApiWrapper;
use Spatie\WebhookClient\Models\WebhookCall;

use function array_merge;
use function compact;

final class DispatchMolliePaymentStatusChangeEvent implements ShouldQueue
{
    private PaymentEndpoint $payments;
    private Dispatcher $events;
    private MostRecentMollieWebhookCallByPayloadFragment $mostRecentMollieWebhookCallByPayloadFragment;

    public function __construct(
        MollieApiWrapper $mollie,
        Dispatcher $events,
        MostRecentMollieWebhookCallByPayloadFragment $mostRecentMollieWebhookCallByPayloadFragment
    ) {
        $this->payments = $mollie->payments();
        $this->events = $events;
        $this->mostRecentMollieWebhookCallByPayloadFragment = $mostRecentMollieWebhookCallByPayloadFragment;
    }

    public function __invoke(PaymentWasUpdatedOnMollie $event): void
    {
        $payment = $this->payments->get($event->paymentId->value());
        $mostRecentWebhookCall = $this->mostRecentMollieWebhookCallByPayloadFragment->before(
            $event->webhookCall,
            ['id' => $event->paymentId->value()],
        );

        $this->enrichWebhookCallPayload($event->webhookCall, ['status' => $payment->status]);

        if (! $this->hasPaymentChanged($mostRecentWebhookCall, $payment->status)) {
            return;
        }

        if ($payment->status === PaymentStatus::STATUS_PAID) {
            $this->events->dispatch(new CustomerHasCompletedPaymentOnMollie($event->paymentId, $payment->status));
        }
    }

    private function hasPaymentChanged(?WebhookCall $mostRecentWebhookCall, string $paymentStatus): bool
    {
        if (! $mostRecentWebhookCall) {
            return true;
        }

        $lastKnownStatus = $mostRecentWebhookCall->payload['status'] ?: '';

        return $lastKnownStatus !== $paymentStatus;
    }

    private function enrichWebhookCallPayload(WebhookCall $webhookCall, array $additionalPayload): void
    {
        $payload = $webhookCall->payload ?: [];
        $payload = array_merge($payload, $additionalPayload);

        $webhookCall->update(compact('payload'));
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(PaymentWasUpdatedOnMollie::class, self::class);
    }
}
