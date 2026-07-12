<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Registration;

use NetCode\Identity\Application\Ports\RegistrationPayload;
use NetCode\Identity\Application\Ports\RegistrationPayloadFactory;

final class DefaultRegistrationPayloadFactory implements RegistrationPayloadFactory
{
    /** @param array<string, mixed> $input */
    public function fromInput(array $input): RegistrationPayload
    {
        return new NoRegistrationPayload;
    }
}
