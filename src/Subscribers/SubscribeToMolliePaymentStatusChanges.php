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
use Mollie\Api\Types\PaymentStatus;
use Mollie\Laravel\Wrappers\MollieApiWrapper;

final class SubscribeToMolliePaymentStatusChanges implements ShouldQueue
{
    private PaymentEndpoint $payments;
    private Dispatcher $events;
    private PaymentHistory $paymentHistory;

    public function __construct(MollieApiWrapper $mollie, Dispatcher $events, PaymentHistory $paymentHistory)
    {
        $this->payments = $mollie->payments();
        $this->events = $events;
        $this->paymentHistory = $paymentHistory;
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
