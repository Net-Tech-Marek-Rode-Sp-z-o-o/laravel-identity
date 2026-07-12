<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exceptions;

use NetCode\Domain\Exception\DomainException;

final class SocialEmailNotVerifiedException extends DomainException
{
    public static function create(): self
    {
        return new self('The social account email is not verified; sign in and link it from your settings instead.');
    }
}
