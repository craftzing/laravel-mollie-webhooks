<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Laravel\Wrappers\MollieApiWrapper;

final class SubscribeToMolliePaymentRefunds implements ShouldQueue
{
    use DispatchesRefundEventsForResources;

    private PaymentEndpoint $payments;
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

        /* @var \Mollie\Api\Resources\Refund $refund */
        foreach ($payment->refunds() as $refund) {
            $refundId = RefundId::fromString($refund->id);
            $hasRefundWithStatusForOrder = $this->paymentHistory->hasRefundWithStatusForPayment(
                $paymentId,
                $refundId,
                $refund->status,
                $event->webhookCall,
            );

            if ($hasRefundWithStatusForOrder) {
                continue;
            }

            $this->dispatchRefundEvents($paymentId, $refundId, $refund);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(MolliePaymentWasUpdated::class, self::class);
    }
}
