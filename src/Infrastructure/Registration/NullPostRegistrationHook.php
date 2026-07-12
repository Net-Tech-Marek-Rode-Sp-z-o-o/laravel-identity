<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Registration;

use NetCode\Identity\Application\Ports\PostRegistrationHook;
use NetCode\Identity\Application\Ports\RegisteredUser;
use NetCode\Identity\Application\Ports\RegistrationPayload;

/** @implements PostRegistrationHook<NoRegistrationPayload> */
final class NullPostRegistrationHook implements PostRegistrationHook
{
    public function afterRegistration(RegisteredUser $user, RegistrationPayload $payload): void {}
}
