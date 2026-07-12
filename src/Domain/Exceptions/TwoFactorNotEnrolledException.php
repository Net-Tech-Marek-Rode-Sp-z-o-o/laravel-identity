<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class TwoFactorNotEnrolledException extends DomainException
{
    public static function create(): self
    {
        return new self('Two-factor authentication is not enrolled for this user.');
    }
}
