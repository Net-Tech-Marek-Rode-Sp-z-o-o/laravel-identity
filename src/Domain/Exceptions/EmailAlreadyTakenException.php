<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;
use NetCode\Identity\Domain\ValueObjects\Email;

final class EmailAlreadyTakenException extends DomainException
{
    public static function for(Email $email): self
    {
        return new self(sprintf('Email <%s> is already registered.', $email->value()));
    }
}
