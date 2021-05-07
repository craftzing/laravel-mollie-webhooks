<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundStatusChangedToRefunded;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Laravel\Wrappers\MollieApiWrapper;

final class SubscribeToMolliePaymentRefunds implements ShouldQueue
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

        if (! $payment->hasRefunds()) {
            return;
        }

        /* @var \Mollie\Api\Resources\Refund $refund */
        foreach ($payment->refunds() as $refund) {
            // Mollie only calls the webhook for a refund when it was actually transferred to
            // the customer. So we should only proceed with the refund if it's transferred.
            if (! $refund->isTransferred()) {
                continue;
            }

            $refundId = RefundId::fromString($refund->id);

            if (! $this->paymentHistory->hasTransferredRefundForPayment($paymentId, $refundId, $event->webhookCall)) {
                $this->events->dispatch(MollieRefundStatusChangedToRefunded::forPayment($paymentId, $refundId));
            }
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MolliePaymentWasUpdated::class, self::class);
    }
}
