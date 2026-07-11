<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exception;

use NetCode\Domain\Exception\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public static function create(): self
    {
        return new self('The provided credentials are incorrect.');
    }
}
