<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class PasswordConfirmationFailedException extends DomainException
{
    public static function create(): self
    {
        return new self('The password confirmation failed.');
    }
}
