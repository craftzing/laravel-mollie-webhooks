<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Queries;

use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Factories\WebhookCallFactory;
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
            fn () => WebhookCallFactory::new()->create(),
        ];

        yield 'Webhook call history has no recent calls from Mollie for payload fragment' => [
            fn (PaymentId $paymentId) => WebhookCallFactory::new()
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
        $ignoreWebhookCall = WebhookCallFactory::new()
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
        $mostRecentWebhookCall = WebhookCallFactory::new()
            ->forPaymentId($paymentId)
            ->withStatusInPayload()
            ->create(['created_at' => 'yesterday 09:00']);
        $olderWebhookCall = WebhookCallFactory::new()
            ->forPaymentId($paymentId)
            ->withStatusInPayload()
            ->create(['created_at' => '2 weeks ago 18:30']);
        $ignoreWebhookCall = WebhookCallFactory::new()
            ->forPaymentId($paymentId)
            ->create();

        $result = $this->app[MostRecentMollieWebhookCallByPayloadFragment::class]->before($ignoreWebhookCall, [
            'id' => $paymentId->value(),
        ]);

        $this->assertInstanceOf(WebhookCall::class, $result);
        $this->assertTrue($result->is($mostRecentWebhookCall));
    }
}
