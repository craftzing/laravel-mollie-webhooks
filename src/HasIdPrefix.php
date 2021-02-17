<?php

declare(strict_types=1);

namespace Craftzing\Laravel\MollieWebhooks;

trait HasIdPrefix
{
    private string $value;

    private function __construct(string $value)
    {
        $this->failWhenPrefixIsInvalid($value);
        $this->value = $value;
    }

    abstract protected function failWhenPrefixIsInvalid(string $value): void;

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
