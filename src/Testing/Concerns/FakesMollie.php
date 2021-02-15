<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Concerns;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieApiClient;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieWebhookCall;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePaymentsEndpoint;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mollie\Laravel\Wrappers\MollieApiWrapper;

use function array_filter;
use function env;
use function random_int;

trait FakesMollie
{
    protected FakePaymentsEndpoint $fakeMolliePayments;

    protected function setupMollieEnv(Application $app): void
    {
        $app['config']->set('mollie.key', env('MOLLIE_KEY'));
    }

    /**
     * @internal
     *
     * Chances are we may want to look for a different solution to fake the mollie SDK at some point (as we preferably
     * wouldn't have fake implementations we don't own ourselves). But for now, this seems to be the simplest approach.
     */
    protected function fakeMollie(): void
    {
        $this->app['config']->set('mollie.key', 'test_fakeMollieKeyContainingAtLeast30Characters');
        $client = new FakeMollieApiClient();
        $this->fakeMolliePayments = new FakePaymentsEndpoint($client);
        $client->payments = $this->fakeMolliePayments;

        $this->swap(
            MollieApiWrapper::class,
            new MollieApiWrapper($this->app[Repository::class], $client),
        );
    }

    protected function paymentId(): PaymentId
    {
        return PaymentId::fromString(PaymentId::PREFIX . Str::random(random_int(4, 16)));
    }

    protected function randomPaymentStatusExcept(string $excludeStatus = ''): string
    {
        $statuses = array_filter(
            FakeMollieWebhookCall::PAYMENT_STATUSES,
            fn (string $status) => $status !== $excludeStatus,
        );

        return Arr::random($statuses);
    }
}
