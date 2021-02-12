<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\CustomerHasCompletedPaymentOnMollie;
use Craftzing\Laravel\MollieWebhooks\Events\PaymentWasUpdatedOnMollie;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Laravel\Wrappers\MollieApiWrapper;

final class DispatchMolliePaymentStatusChangeEvents implements ShouldQueue
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

    public function __invoke(PaymentWasUpdatedOnMollie $event): void
    {
        $payment = $this->payments->get($event->paymentId->value());

        if ($this->paymentHistory->hasLatestStatus($payment->status, $payment)) {
            return;
        }

        if ($payment->status === PaymentStatus::STATUS_PAID) {
            $this->events->dispatch(new CustomerHasCompletedPaymentOnMollie($event->paymentId, $payment->status));
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(PaymentWasUpdatedOnMollie::class, self::class);
    }
}
