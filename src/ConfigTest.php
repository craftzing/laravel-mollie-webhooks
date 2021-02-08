<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Exceptions\AppMisconfigured;
use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;
use Exception;
use Generator;
use Illuminate\Support\Str;

use function config;

final class ConfigTest extends IntegrationTestCase
{
    protected bool $shouldFakeConfig = false;

    public function misconfiguredApp(): Generator
    {
        yield 'Value is undefined' => [
            ['laravel-mollie-webhooks.value' => null],
            AppMisconfigured::missingConfigValue(),
        ];
    }

    /**
     * @test
     * @dataProvider misconfiguredApp
     */
    public function itFailsToResolveWhenTheAppIsMisconfigured(array $config, Exception $exception): void
    {
        config($config);

        $this->expectExceptionObject($exception);

        $this->app[Config::class];
    }
}
