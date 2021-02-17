<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Subscribers;

use Craftzing\Laravel\MollieWebhooks\Events\MolliePaymentWasUpdated;
use Craftzing\Laravel\MollieWebhooks\Events\MollieRefundWasTransferred;
use Craftzing\Laravel\MollieWebhooks\Payments\PaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePayment;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\Payments\FakePaymentHistory;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\Testing\TruthTest;
use Generator;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mollie\Api\Types\RefundStatus;
use Spatie\WebhookClient\Models\WebhookCall;

final class SubscribeToMolliePaymentRefundsTest extends IntegrationTestCase
{
    private FakePaymentHistory $fakePaymentHistory;

    /**
     * @before
     */
    public function fakePaymentHistory(): void
    {
        $this->afterApplicationCreated(function (): void {
            $this->swap(PaymentHistory::class, $this->fakePaymentHistory = new FakePaymentHistory());
        });
    }

    /**
     * @test
     */
    public function itCanBeRegisteredAsAQueuedSubscriberForTheGenericPaymentEvent(): void
    {
        $this->dontFakeEvents();

        Event::subscribe(SubscribeToMolliePaymentRefunds::class);
        Event::dispatch(new MolliePaymentWasUpdated($this->generatePaymentId(), new WebhookCall()));

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $listener): bool {
            return $listener->class === SubscribeToMolliePaymentRefunds::class;
        });
    }

    /**
     * @test
     */
    public function itCanHandlePaymentsWithoutRefunds(): void
    {
        $payment = FakePayment::fake($this->app);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($payment->id())
            ->create();

        $this->app[SubscribeToMolliePaymentRefunds::class](new MolliePaymentWasUpdated($payment->id(), $webhookCall));

        Event::assertNotDispatched(MollieRefundWasTransferred::class);
    }

    public function nonTransferredRefund(): Generator
    {
        foreach (FakeRefund::STATUSES as $status) {
            if ($status !== RefundStatus::STATUS_REFUNDED) {
                yield "Refund status is `$status`" => [$status];
            }
        }
    }

    /**
     * @test
     * @dataProvider nonTransferredRefund
     */
    public function itCanHandlePaymentsWithoutATransferredRefund(string $status): void
    {
        $refund = FakeRefund::fake($this->app)->withStatus($status);
        $payment = FakePayment::fake($this->app)->withRefund($refund);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($payment->id())
            ->create();

        $this->app[SubscribeToMolliePaymentRefunds::class](new MolliePaymentWasUpdated($payment->id(), $webhookCall));

        Event::assertNotDispatched(MollieRefundWasTransferred::class);
    }

    /**
     * @test
     */
    public function itCanHandlePaymentsWithMultipleRefunds(): void
    {
        $notTransferredRefund = FakeRefund::fake($this->app)->notTransferred();
        $transferredRefund = FakeRefund::fake($this->app)->transferred();
        $payment = FakePayment::fake($this->app)
            ->withRefund($notTransferredRefund)
            ->withRefund($transferredRefund);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($payment->id())
            ->create();

        $this->app[SubscribeToMolliePaymentRefunds::class](new MolliePaymentWasUpdated($payment->id(), $webhookCall));

        Event::assertDispatchedTimes(MollieRefundWasTransferred::class);
        Event::assertDispatched(
            MollieRefundWasTransferred::class,
            new TruthTest(function (MollieRefundWasTransferred $event) use ($payment, $transferredRefund): void {
                $this->assertEquals($payment->id(), $event->resourceId);
                $this->assertEquals($transferredRefund->id(), $event->refundId);
            }),
        );
    }

    public function paymentHistory(): Generator
    {
        yield 'Transferred refund exists payment history' => [
            function (FakePaymentHistory $fakePaymentHistory): bool {
                $fakePaymentHistory->fakeHasTransferredRefundForPayment();

                return true;
            },
        ];

        yield 'Transferred refund does not exists payment history' => [
            fn (): bool => false,
        ];
    }

    /**
     * @test
     * @dataProvider paymentHistory
     */
    public function itCanHandleWebhookCallsIndicatingARefundWasTransferredForAPayment(callable $addPaymentHistory): void
    {
        $refund = FakeRefund::fake($this->app)->transferred();
        $payment = FakePayment::fake($this->app)->withRefund($refund);
        $hasTransferredRefundForPaymentInHistory = $addPaymentHistory($this->fakePaymentHistory);
        $webhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($payment->id())
            ->create();

        $this->app[SubscribeToMolliePaymentRefunds::class](new MolliePaymentWasUpdated($payment->id(), $webhookCall));

        if ($hasTransferredRefundForPaymentInHistory) {
            Event::assertNotDispatched(MollieRefundWasTransferred::class);
        } else {
            Event::assertDispatched(
                MollieRefundWasTransferred::class,
                new TruthTest(function (MollieRefundWasTransferred $event) use ($payment, $refund): void {
                    $this->assertEquals($payment->id(), $event->resourceId);
                    $this->assertEquals($refund->id(), $event->refundId);
                }),
            );
        }
    }
}
