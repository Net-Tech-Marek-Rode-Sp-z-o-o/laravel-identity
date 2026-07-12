<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class SocialAccountAlreadyLinkedException extends DomainException
{
    public static function create(): self
    {
        return new self('This social account is already linked to another user.');
    }
}
