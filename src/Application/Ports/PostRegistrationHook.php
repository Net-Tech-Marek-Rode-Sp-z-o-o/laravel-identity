<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

/** @template TPayload of RegistrationPayload */
interface PostRegistrationHook
{
    /**
     * Runs inside the registration transaction, after the user is persisted.
     *
     * @param TPayload $payload
     */
    public function afterRegistration(RegisteredUser $user, RegistrationPayload $payload): void;
}
