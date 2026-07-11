<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

use DateTimeImmutable;
use NetCode\Identity\Domain\ValueObjects\Email;

interface PasswordResetNotifier
{
    public function notify(Email $email, string $token, DateTimeImmutable $expiresAt): void;
}
