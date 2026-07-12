<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\PostRegistrationHook;
use NetCode\Identity\Application\Ports\RegisteredUser;
use NetCode\Identity\Application\Ports\RegistrationPayload;

/** @implements PostRegistrationHook<RegistrationPayload> */
final class RecordingPostRegistrationHook implements PostRegistrationHook
{
    public RegisteredUser|null $user = null;

    public RegistrationPayload|null $payload = null;

    public function afterRegistration(RegisteredUser $user, RegistrationPayload $payload): void
    {
        $this->user = $user;
        $this->payload = $payload;
    }
}
