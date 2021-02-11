<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Queries;

use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Generator;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\WebhookClient\Models\WebhookCall;

final class MostRecentMollieWebhookCallByPayloadFragmentTest extends IntegrationTestCase
{
    use WithFaker;

    public function noResults(): Generator
    {
        yield 'Webhook call history is empty' => [
            fn () => null,
        ];

        yield 'Webhook call history has no recent calls for payload fragment' => [
            fn () => FakeMollieWebhookCall::new()->create(),
        ];

        yield 'Webhook call history has no recent calls from Mollie for payload fragment' => [
            fn (PaymentId $paymentId) => FakeMollieWebhookCall::new()
                ->forPaymentId($paymentId)
                ->create(['name' => $this->makeFaker()->name]),
        ];
    }

    /**
     * @test
     * @dataProvider noResults
     */
    public function itCanHandleNoResults(callable $addWebhookCallHistory): void
    {
        $paymentId = $this->paymentId();
        $addWebhookCallHistory($paymentId);
        $ignoreWebhookCall = FakeMollieWebhookCall::new()
            ->forPaymentId($paymentId)
            ->create();

        $result = $this->app[MostRecentMollieWebhookCallByPayloadFragment::class]->before($ignoreWebhookCall, [
            'id' => $paymentId->value(),
        ]);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function itCanReturnTheMostRecentMollieWebhookCallByPayloadFragment(): void
    {
        $paymentId = $this->paymentId();
        $mostRecentWebhookCall = FakeMollieWebhookCall::new()
            ->forPaymentId($paymentId)
            ->withStatusInPayload()
            ->create(['created_at' => 'yesterday 09:00']);
        $olderWebhookCall = FakeMollieWebhookCall::new()
            ->forPaymentId($paymentId)
            ->withStatusInPayload()
            ->create(['created_at' => '2 weeks ago 18:30']);
        $ignoreWebhookCall = FakeMollieWebhookCall::new()
            ->forPaymentId($paymentId)
            ->create();

        $result = $this->app[MostRecentMollieWebhookCallByPayloadFragment::class]->before($ignoreWebhookCall, [
            'id' => $paymentId->value(),
        ]);

        $this->assertInstanceOf(WebhookCall::class, $result);
        $this->assertTrue($result->is($mostRecentWebhookCall));
    }
}
