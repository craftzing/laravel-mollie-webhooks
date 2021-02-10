<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Testing\IntegrationTestCase;

use function config;

final class ConfigTest extends IntegrationTestCase
{
    protected bool $shouldFakeConfig = false;

    /**
     * @test
     * @group without-laravel-mollie-sdk
     */
    public function itReturnsFalseWhenTheLaravelMollieSdkIsNotInstalled(): void
    {
        $this->assertFalse(
            $this->app[Config::class]->isLaravelMollieSdkInstalled(),
        );
    }

    /**
     * @test
     * @group requires-laravel-mollie-sdk
     */
    public function itReturnsTrueWhenTheLaravelMollieSdkIsInstalled(): void
    {
        $this->assertTrue(
            $this->app[Config::class]->isLaravelMollieSdkInstalled(),
        );
    }
}
