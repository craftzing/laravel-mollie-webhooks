<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Concerns;

use Craftzing\Laravel\MollieWebhooks\Payments\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Refunds\RefundId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieApiClient;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePayment;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePaymentsEndpoint;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeRefund;
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
    /**
     * This option should only be disabled when you need to fetch real data from the Mollie
     * API when writing test-cases. Don't disable it within the test suite itself.
     */
    protected bool $shouldFakeMollie = true;

    protected function setupMollieEnv(Application $app): void
    {
        $app['config']->set('mollie.key', env('MOLLIE_KEY'));
    }

    /**
     * Chances are we may want to look for a different solution to fake the mollie SDK at some point (as we preferably
     * wouldn't have fake implementations we don't own ourselves). But for now, this seems to be the simplest approach.
     */
    protected function fakeMollie(): void
    {
        $this->app['config']->set('mollie.key', 'test_fakeMollieKeyContainingAtLeast30Characters');
        $client = FakeMollieApiClient::fake($this->app);
        $client->payments = FakePaymentsEndpoint::fake($this->app);

        $this->swap(
            MollieApiWrapper::class,
            new MollieApiWrapper($this->app[Repository::class], $client),
        );
    }

    protected function generatePaymentId(): PaymentId
    {
        return PaymentId::fromString(PaymentId::PREFIX . Str::random(random_int(4, 16)));
    }

    protected function generateRefundId(): RefundId
    {
        return RefundId::fromString(RefundId::PREFIX . Str::random(random_int(4, 16)));
    }

    protected function randomPaymentStatusExcept(string $excludeStatus = ''): string
    {
        $statuses = array_filter(
            FakePayment::STATUSES,
            fn (string $status) => $status !== $excludeStatus,
        );

        return Arr::random($statuses);
    }

    protected function randomRefundStatusExcept(string $excludeStatus = ''): string
    {
        $statuses = array_filter(
            FakeRefund::STATUSES,
            fn (string $status) => $status !== $excludeStatus,
        );

        return Arr::random($statuses);
    }
}
