<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Exception;
use Mollie\Api\MollieApiClient;
use stdClass;

final class FakeMollieApiClient extends MollieApiClient
{
    public function performHttpCall($httpMethod, $apiMethod, $httpBody = null): stdClass
    {
        $this->failOnAttemptToPerformHttpCall();
    }

    public function performHttpCallToFullUrl($httpMethod, $url, $httpBody = null): stdClass
    {
        $this->failOnAttemptToPerformHttpCall();
    }

    private function failOnAttemptToPerformHttpCall(): void
    {
        throw new Exception(
            'Http calls should not be performed when Mollie is faked. ' .
            'Make sure to fake the endpoint call in the according endpoint fake.',
        );
    }
}
