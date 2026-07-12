<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Registration;

use Illuminate\Http\Request;
use NetCode\Identity\Application\Ports\RegistrationPayload;
use NetCode\Identity\Application\Ports\RegistrationPayloadFactory;

final class DefaultRegistrationPayloadFactory implements RegistrationPayloadFactory
{
    public function fromRequest(Request $request): RegistrationPayload
    {
        return new NoRegistrationPayload;
    }
}
