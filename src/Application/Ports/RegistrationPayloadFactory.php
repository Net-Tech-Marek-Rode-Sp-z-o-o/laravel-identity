<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface RegistrationPayloadFactory
{
    /** @param array<string, mixed> $input */
    public function fromInput(array $input): RegistrationPayload;
}
