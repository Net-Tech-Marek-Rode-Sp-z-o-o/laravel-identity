<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface InvitationMetadataFactory
{
    /** @return array<string, mixed> */
    public function for(string $inviterId): array;
}
