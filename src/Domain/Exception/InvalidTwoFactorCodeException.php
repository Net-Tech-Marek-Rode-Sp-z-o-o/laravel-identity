<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Exception;

use NetCode\Domain\Exception\DomainException;

final class InvalidTwoFactorCodeException extends DomainException
{
    public static function create(): self
    {
        return new self('The two-factor code is invalid.');
    }
}
