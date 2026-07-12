<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use DateTimeImmutable;
use NetCode\Identity\Application\Ports\InvitationNotifier;
use NetCode\Identity\Domain\ValueObjects\Email;

final class SpyInvitationNotifier implements InvitationNotifier
{
    public Email|null $email = null;

    public string|null $token = null;

    public DateTimeImmutable|null $expiresAt = null;

    public function notify(Email $email, string $token, DateTimeImmutable $expiresAt): void
    {
        $this->email = $email;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
    }
}
