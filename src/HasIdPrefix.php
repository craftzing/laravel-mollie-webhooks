<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

use Craftzing\Laravel\MollieWebhooks\Exceptions\InvalidResourceId;
use Illuminate\Support\Str;

trait HasIdPrefix
{
    private string $value;

    private function __construct(string $value)
    {
        $this->failWhenPrefixIsInvalid($value);
        $this->value = $value;
    }

    abstract protected function prefix(): string;

    protected function failWhenPrefixIsInvalid(string $value): void
    {
        if (! Str::startsWith($value, $this->prefix())) {
            throw InvalidResourceId::missingExpectedPrefix($value, $this->prefix());
        }
    }

    /**
     * @return static
     */
    public static function fromString(string $value): self
    {
        return new static($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
