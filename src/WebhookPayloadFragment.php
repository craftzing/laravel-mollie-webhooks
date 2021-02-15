<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

final class WebhookPayloadFragment
{
    /**
     * @var array<string>
     */
    private array $keys = [];

    private function __construct(string ...$keys)
    {
        $this->keys = $keys;
    }

    public static function fromKeys(string ...$keys): self
    {
        return new self(...$keys);
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return $this->keys;
    }
}
