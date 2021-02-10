<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Mollie\Laravel\Wrappers\MollieApiWrapper;

use function class_exists;

final class IlluminateConfig implements Config
{
    public function isLaravelMollieSdkInstalled(): bool
    {
        return class_exists(MollieApiWrapper::class);
    }
}
