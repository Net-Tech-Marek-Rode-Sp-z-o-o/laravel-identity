<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\RegistrationPayload;

final readonly class OrganizationPayload implements RegistrationPayload
{
    public function __construct(
        public string $organizationName,
    ) {}
}
