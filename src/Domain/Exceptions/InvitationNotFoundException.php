<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class InvitationNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('No invitation exists with id <%s>.', $id));
    }
}
