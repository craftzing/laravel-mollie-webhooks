<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Queries;

use Craftzing\Laravel\MollieWebhooks\ResourceId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Craftzing\Laravel\MollieWebhooks\WebhookPayloadFragment;
use Generator;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\WebhookClient\Models\WebhookCall;

final class LatestMollieWebhookCallByResourceIdTest extends IntegrationTestCase
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
            fn (ResourceId $resourceId) => FakeMollieWebhookCall::new()
                ->forResourceId($resourceId)
                ->create(['name' => $this->makeFaker()->name]),
        ];
    }

    /**
     * @test
     * @dataProvider noResults
     */
    public function itCanHandleNoResults(callable $addWebhookCallHistory): void
    {
        $resourceId = $this->paymentId();
        $addWebhookCallHistory($resourceId);
        $ignoreWebhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($resourceId)
            ->create();

        $result = $this->app[LatestMollieWebhookCallByResourceId::class]->find($resourceId, $ignoreWebhookCall);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function itCanFindTheLatestMollieWebhookCallByResourceId(): void
    {
        $resourceId = $this->paymentId();
        $latestWebhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($resourceId)
            ->withStatusInPayload()
            ->create(['created_at' => 'yesterday 09:00']);
        $olderWebhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($resourceId)
            ->withStatusInPayload()
            ->create(['created_at' => '2 weeks ago 18:30']);
        $ignoreWebhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($resourceId)
            ->create();

        $result = $this->app[LatestMollieWebhookCallByResourceId::class]->find($resourceId, $ignoreWebhookCall);

        $this->assertInstanceOf(WebhookCall::class, $result);
        $this->assertTrue($result->is($latestWebhookCall));
    }

    /**
     * @test
     */
    public function itCanBeFilteredByAPayloadFragment(): void
    {
        $resourceId = $this->paymentId();
        $webhookCallWithOnlyStatusInPayload = FakeMollieWebhookCall::new()
            ->forResourceId($resourceId)
            ->withStatusInPayload()
            ->create();
        $webhookCallWithFragmentInPayload = FakeMollieWebhookCall::new()
            ->forResourceId($resourceId)
            ->withStatusInPayload()
            ->appendToPayload(['foo' => 'bar'])
            ->create();
        $webhookCallWithoutFragmentInPayload = FakeMollieWebhookCall::new()
            ->forResourceId($resourceId)
            ->appendToPayload(['bar' => 'foo'])
            ->create();
        $ignoreWebhookCall = FakeMollieWebhookCall::new()
            ->forResourceId($resourceId)
            ->create();

        $result = $this->app[LatestMollieWebhookCallByResourceId::class]->find(
            $resourceId,
            $ignoreWebhookCall,
            WebhookPayloadFragment::fromKeys('status', 'foo'),
        );

        $this->assertInstanceOf(WebhookCall::class, $result);
        $this->assertTrue($result->is($webhookCallWithFragmentInPayload));
    }
}
