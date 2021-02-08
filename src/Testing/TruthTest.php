<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks\Testing;

use function call_user_func_array;

final class TruthTest
{
    /**
     * @var callable
     */
    private $assertion;

    public function __construct(callable $assertion)
    {
        $this->assertion = $assertion;
    }

    public function __invoke(...$arguments): bool
    {
        call_user_func_array($this->assertion, $arguments);

        return true;
    }
}
