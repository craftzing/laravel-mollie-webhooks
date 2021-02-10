<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\CustomerHasCompletedPaymentOnMollie;
use Craftzing\Laravel\MollieWebhooks\Events\PaymentWasUpdatedOnMollie;
use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Factories\WebhookCallFactory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\Testing\TruthTest;
use Generator;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Event as IlluminateEvent;
use Illuminate\Support\Facades\Queue;
use Mollie\Api\Types\PaymentStatus;
use Spatie\WebhookClient\Models\WebhookCall;

use function json_encode;

final class DispatchMolliePaymentStatusChangeEventTest extends IntegrationTestCase
{
    /**
     * @test
     */
    public function itCanBeRegisteredAsAQueuedSubscriberForTheGenericPaymentEvent(): void
    {
        $this->dontFakeEvents();

        IlluminateEvent::subscribe(DispatchMolliePaymentStatusChangeEvent::class);
        IlluminateEvent::dispatch(new PaymentWasUpdatedOnMollie($this->paymentId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === DispatchMolliePaymentStatusChangeEvent::class;
        });
    }

    public function webhookCallHistoryForPayments(): Generator
    {
        yield 'Payment has no webhook call history' => [
            fn () => null,
        ];

        foreach (WebhookCallFactory::PAYMENT_STATUSES as $paymentStatus) {
            yield "Payment is currently marked as `$paymentStatus` in the webhook call history" => [
                function (PaymentId $paymentId) use ($paymentStatus): WebhookCall {
                    $webhookCallWithoutPaymentStatus = WebhookCallFactory::new()
                        ->forPaymentId($paymentId)
                        ->create();

                    return WebhookCallFactory::new()
                        ->forPaymentId($paymentId)
                        ->withStatusInPayload($paymentStatus)
                        ->create();
                },
            ];
        }
    }

    /**
     * @test
     * @dataProvider webhookCallHistoryForPayments
     */
    public function itCanHandleWebhookCallsIndicatingAPaymentStatusChangedToPaid(callable $addWebhookCallHistory): void
    {
        $updatedStatus = PaymentStatus::STATUS_PAID;
        $payment = $this->fakeMolliePayments->fakePaymentWithStatus($updatedStatus);
        $paymentId = new PaymentId($payment->id);
        $lastKnownPaymentStatus = optional(
            $addWebhookCallHistory($paymentId),
            fn (WebhookCall $webhookCall) => $webhookCall->payload['status'] ?: null,
        );
        $webhookCall = WebhookCallFactory::new()
            ->forPaymentId($paymentId)
            ->create();

        $this->app[DispatchMolliePaymentStatusChangeEvent::class](
            new PaymentWasUpdatedOnMollie($paymentId, $webhookCall),
        );

        $this->assertDatabaseHas(WebhookCallFactory::TABLE, [
            'id' => $webhookCall->getKey(),
            'payload' => json_encode([
                'id' => $paymentId->value(),
                'status' => PaymentStatus::STATUS_PAID,
            ]),
        ]);

        if ($lastKnownPaymentStatus === $updatedStatus) {
            Event::assertNotDispatched(CustomerHasCompletedPaymentOnMollie::class);

            return;
        }

        Event::assertDispatched(
            CustomerHasCompletedPaymentOnMollie::class,
            new TruthTest(function (CustomerHasCompletedPaymentOnMollie $event) use ($paymentId, $updatedStatus): void {
                $this->assertSame($paymentId, $event->paymentId);
                $this->assertSame($updatedStatus, $event->status);
            }),
        );
    }
}
