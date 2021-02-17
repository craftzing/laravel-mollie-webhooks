<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use function tap;

final class WebhookPayloadFragment
{
    /**
     * @var array<string>
     */
    private array $keys = [];

    /**
     * @var array<string>
     */
    private array $values = [];

    public static function fromKeys(string ...$keys): self
    {
        return tap(new self(), fn (self $instance) => $instance->keys = $keys);
    }

    /**
     * @param array<mixed> $values
     */
    public static function fromValues(array $values): self
    {
        return tap(new self(), fn (self $instance) => $instance->values = $values);
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return $this->keys;
    }

    /**
     * @return array<mixed>
     */
    public function values(): array
    {
        return $this->values;
    }
}
