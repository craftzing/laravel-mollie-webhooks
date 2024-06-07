<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePayment;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments\FakePaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\WebhookClient\Models\WebhookCall;

final class SubscribeToMolliePaymentRefundsTest extends IntegrationTestCase
{
    private FakePaymentHistory $fakePaymentHistory;

    protected array $eventsToFake = FakeRefund::STATUS_EVENTS;

    #[Before]
    public function fakePaymentHistory(): void
    {
        $this->afterApplicationCreated(function (): void {
            $this->swap(PaymentHistory::class, $this->fakePaymentHistory = new FakePaymentHistory());
        });
    }

    #[Test]
    public function itCanBeRegisteredAsAQueuedSubscriberForTheGenericPaymentEvent(): void
    {
        $this->dontFakeEvents();

        Event::subscribe(SubscribeToMolliePaymentRefunds::class);
        Event::dispatch(new MolliePaymentWasUpdated($this->generatePaymentId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === SubscribeToMolliePaymentRefunds::class;
        });
    }

    public static function paymentHistory(): Generator
    {
        yield 'Payment has no refunds' => [
            fn (): array => [],
        ];

        foreach (FakeRefund::STATUSES as $refundStatus) {
            yield "Payment has a $refundStatus refund" => [
                fn (Application $app): array => [
                    FakeRefund::fake($app)->withStatus($refundStatus),
                ],
            ];
        }

        yield 'Payment has multiple refunds' => [
            fn (Application $app): array => [
                FakeRefund::fake($app)->withStatus(),
                FakeRefund::fake($app)->withStatus(),
            ],
        ];
    }

    #[Test]
    #[DataProvider('paymentHistory')]
    public function itDispatchesRefundStatusEventsWhenTheStatusIsNotInThePaymentHistory(callable $resolveRefunds): void
    {
        /** @var \Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund[] $refunds */
        $refunds = $resolveRefunds($this->app);
        $payment = FakePayment::fake($this->app);
        if ($refunds !== []) {
            $payment->withRefunds(...$refunds);
        }
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($payment->id())
            ->create();

        $this->app[SubscribeToMolliePaymentRefunds::class](new MolliePaymentWasUpdated($payment->id(), $webhookCall));

        if (! $refunds) {
            $this->assertNoEventsWereDispatched();
        }

        foreach ($refunds as $refund) {
            Event::assertDispatched(
                $refund->statusEventClass(),
                fn (object $event): bool =>
                    $payment->id()->value() === $event->resourceId->value()
                    && $refund->id()->value() === $event->refundId->value(),
            );
        }
    }

    #[Test]
    #[DataProvider('paymentHistory')]
    public function itDoesNotDispatchRefundStatusEventsWhenTheStatusExistsInThePaymentHistory(
        callable $resolveRefunds
    ): void {
        /** @var \Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund[] $refunds */
        $refunds = $resolveRefunds($this->app);
        $payment = FakePayment::fake($this->app);
        if ($refunds !== []) {
            $payment->withRefunds(...$refunds);
        }
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($payment->id())
            ->create();

        foreach ($refunds as $refund) {
            $this->fakePaymentHistory->fakeHasTransferredRefundForPayment($payment->id(), $refund->id(), $refund->status);
        }

        $this->app[SubscribeToMolliePaymentRefunds::class](new MolliePaymentWasUpdated($payment->id(), $webhookCall));

        $this->assertNoEventsWereDispatched();
    }
}
