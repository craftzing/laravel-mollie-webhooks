<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Concerns;

use Craftzing\Laravel\MollieWebhooks\PaymentId;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakeMollieApiClient;
use Craftzing\Laravel\MollieWebhooks\Testing\Doubles\FakePaymentsEndpoint;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;
use Mollie\Laravel\Wrappers\MollieApiWrapper;

use function random_int;

trait FakesMollie
{
    protected FakePaymentsEndpoint $fakeMolliePayments;

    /**
     * @internal
     *
     * Chances are we may want to look for a different solution to fake the mollie SDK at some point (as we preferably
     * wouldn't have fake implementations we don't own ourselves). But for now, this seems to be the simplest approach.
     */
    protected function fakeMollie(): void
    {
        $client = new FakeMollieApiClient();
        $this->fakeMolliePayments = new FakePaymentsEndpoint($client);
        $client->payments = $this->fakeMolliePayments;

        $this->app->extend(
            MollieApiWrapper::class,
            fn () => new MollieApiWrapper($this->app[Repository::class], $client),
        );
    }

    protected function paymentId(): PaymentId
    {
        return PaymentId::fromString(PaymentId::PREFIX . Str::random(random_int(4, 16)));
    }
}
