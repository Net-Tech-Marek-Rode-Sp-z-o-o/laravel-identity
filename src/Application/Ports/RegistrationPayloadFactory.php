<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

use Illuminate\Http\Request;

interface RegistrationPayloadFactory
{
    public function fromRequest(Request $request): RegistrationPayload;
}
