<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class UserNotFoundException extends DomainException
{
    public static function withId(UserId $id): self
    {
        return new self(sprintf('User <%s> was not found.', $id->value()));
    }

    public static function withEmail(Email $email): self
    {
        return new self(sprintf('User <%s> was not found.', $email->value()));
    }
}
