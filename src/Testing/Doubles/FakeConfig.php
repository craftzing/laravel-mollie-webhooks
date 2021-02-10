<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing\Doubles;

use Craftzing\Laravel\MollieWebhooks\Config;
use Illuminate\Contracts\Foundation\Application;

/**
 * @internal This implementation should only be used for testing purposes.
 */
final class FakeConfig implements Config
{
    public static function swap(Application $app): self
    {
        return $app->instance(Config::class, new self());
    }

    public function isLaravelMollieSdkInstalled(): bool
    {
        return false;
    }
}
