<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use Illuminate\Http\Request;
use NetCode\Identity\Application\Ports\RegistrationPayload;
use NetCode\Identity\Application\Ports\RegistrationPayloadFactory;

final class OrganizationPayloadFactory implements RegistrationPayloadFactory
{
    public function fromRequest(Request $request): RegistrationPayload
    {
        return new OrganizationPayload((string) $request->input('organization_name', ''));
    }
}
