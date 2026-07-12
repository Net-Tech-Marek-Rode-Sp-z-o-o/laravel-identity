<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\RegistrationPayload;
use NetCode\Identity\Application\Ports\RegistrationPayloadFactory;

final class OrganizationPayloadFactory implements RegistrationPayloadFactory
{
    /** @param array<string, mixed> $input */
    public function fromInput(array $input): RegistrationPayload
    {
        return new OrganizationPayload((string) ($input['organization_name'] ?? ''));
    }
}
