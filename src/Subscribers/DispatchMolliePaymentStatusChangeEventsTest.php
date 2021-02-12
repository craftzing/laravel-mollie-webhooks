<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\CustomerHasCompletedPaymentOnMollie;
use Craftzing\Laravel\MollieWebhooks\Events\PaymentWasUpdatedOnMollie;
use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments\FakePaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\Testing\TruthTest;
use Generator;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Event as IlluminateEvent;
use Illuminate\Support\Facades\Queue;
use Mollie\Api\Types\PaymentStatus;
use Spatie\WebhookClient\Models\WebhookCall;

final class DispatchMolliePaymentStatusChangeEventsTest extends IntegrationTestCase
{
    private FakePaymentHistory $fakePaymentHistory;

    /**
     * @before
     */
    public function fakePaymentHistory(): void
    {
        $this->afterApplicationCreated(function () {
            $this->swap(PaymentHistory::class, $this->fakePaymentHistory = new FakePaymentHistory());
        });
    }

    /**
     * @test
     */
    public function itCanBeRegisteredAsAQueuedSubscriberForTheGenericPaymentEvent(): void
    {
        $this->dontFakeEvents();

        IlluminateEvent::subscribe(DispatchMolliePaymentStatusChangeEvents::class);
        IlluminateEvent::dispatch(new PaymentWasUpdatedOnMollie($this->paymentId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === DispatchMolliePaymentStatusChangeEvents::class;
        });
    }

    public function paymentHistory(): Generator
    {
        yield 'Payment history does not have status for the payment yet' => [
            fn () => null,
        ];

        foreach (FakeMollieWebhookCall::PAYMENT_STATUSES as $paymentStatus) {
            yield "Payment history has `$paymentStatus` as the latest status for the payment" => [
                function (FakePaymentHistory $fakePaymentHistory) use ($paymentStatus): string {
                    $fakePaymentHistory->fakeLatestStatus($paymentStatus);

                    return $paymentStatus;
                },
            ];
        }
    }

    /**
     * @test
     * @dataProvider paymentHistory
     */
    public function itCanHandleWebhookCallsIndicatingAPaymentStatusWasChangedToPaid(callable $addPaymentHistory): void
    {
        $updatedStatus = PaymentStatus::STATUS_PAID;
        $payment = $this->fakeMolliePayments->fakePaymentWithStatus($updatedStatus);
        $latestStatusInPaymentHistory = $addPaymentHistory($this->fakePaymentHistory);
        $paymentId = new PaymentId($payment->id);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forPaymentId($paymentId)
            ->create();

        $this->app[DispatchMolliePaymentStatusChangeEvents::class](
            new PaymentWasUpdatedOnMollie($paymentId, $webhookCall),
        );

        if ($latestStatusInPaymentHistory === $updatedStatus) {
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
