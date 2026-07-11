<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\ValueObjects;

use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Domain\ValueObject;
use Stringable;

final class Email extends ValueObject implements Stringable
{
    private readonly string $value;

    public function __construct(
        string $value,
    ) {
        $normalised = strtolower(trim($value));

        if (filter_var($normalised, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf('<%s> is not a valid email address.', $value));
        }

        $this->value = $normalised;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->value === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
