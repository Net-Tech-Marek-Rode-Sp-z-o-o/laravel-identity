<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class InvalidVerificationTokenException extends DomainException
{
    public static function notFound(): self
    {
        return new self('The e-mail verification token is invalid.');
    }

    public static function expired(): self
    {
        return new self('The e-mail verification token has expired.');
    }

    public static function alreadyUsed(): self
    {
        return new self('The e-mail verification token has already been used.');
    }
}
