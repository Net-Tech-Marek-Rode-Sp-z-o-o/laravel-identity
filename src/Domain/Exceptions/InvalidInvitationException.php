<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class InvalidInvitationException extends DomainException
{
    public static function withoutInviter(): self
    {
        return new self('The invitation has no inviter and cannot be accepted.');
    }

    public static function notFound(): self
    {
        return new self('The invitation is invalid.');
    }

    public static function expired(): self
    {
        return new self('The invitation has expired.');
    }

    public static function alreadyAccepted(): self
    {
        return new self('The invitation has already been accepted.');
    }

    public static function revoked(): self
    {
        return new self('The invitation has been revoked.');
    }
}
