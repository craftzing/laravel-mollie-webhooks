<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

interface Config
{
    public function isLaravelMollieSdkInstalled(): bool;
}
