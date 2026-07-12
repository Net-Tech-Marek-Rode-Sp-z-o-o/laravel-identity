<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

use DateTimeImmutable;
use NetCode\Identity\Domain\ValueObjects\Email;

interface InvitationNotifier
{
    public function notify(Email $email, string $token, DateTimeImmutable $expiresAt): void;
}
