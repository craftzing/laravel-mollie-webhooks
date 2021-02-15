<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

interface ResourceId
{
    public function __toString(): string;

    public function value(): string;
}
