<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToCanceled;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToExpired;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToFailed;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentStatusChangedToPaid;
use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\PaymentStatus;

final class SubscribeToMolliePaymentStatusChanges implements ShouldQueue
{
    private readonly PaymentEndpoint $payments;

    public function __construct(
        MollieApiClient $mollie,
        private readonly Dispatcher $events,
        private readonly PaymentHistory $paymentHistory,
    ) {
        $this->payments = $mollie->payments;
    }

    public function __invoke(MolliePaymentWasUpdated $event): void
    {
        $paymentId = $event->paymentId;
        $payment = $this->payments->get($paymentId->value());

        if ($this->paymentHistory->hasLatestStatusForPayment($paymentId, $payment->status, $event->webhookCall)) {
            return;
        }

        if ($payment->status === PaymentStatus::STATUS_PAID) {
            $this->events->dispatch(new MolliePaymentStatusChangedToPaid($paymentId));

            return;
        }

        if ($payment->status === PaymentStatus::STATUS_EXPIRED) {
            $this->events->dispatch(new MolliePaymentStatusChangedToExpired($paymentId));

            return;
        }

        if ($payment->status === PaymentStatus::STATUS_FAILED) {
            $this->events->dispatch(new MolliePaymentStatusChangedToFailed($paymentId));

            return;
        }

        if ($payment->status === PaymentStatus::STATUS_CANCELED) {
            $this->events->dispatch(new MolliePaymentStatusChangedToCanceled($paymentId));

            return;
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MolliePaymentWasUpdated::class, self::class);
    }
}
