<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class InvalidResetTokenException extends DomainException
{
    public static function notFound(): self
    {
        return new self('The password reset token is invalid.');
    }

    public static function expired(): self
    {
        return new self('The password reset token has expired.');
    }

    public static function alreadyUsed(): self
    {
        return new self('The password reset token has already been used.');
    }
}
