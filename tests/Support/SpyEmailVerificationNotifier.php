<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use DateTimeImmutable;
use NetCode\Identity\Application\Ports\EmailVerificationNotifier;
use NetCode\Identity\Domain\ValueObjects\Email;

final class SpyEmailVerificationNotifier implements EmailVerificationNotifier
{
    public Email|null $email = null;

    public string|null $token = null;

    public int $sent = 0;

    public function notify(Email $email, string $token, DateTimeImmutable $expiresAt): void
    {
        $this->email = $email;
        $this->token = $token;
        $this->sent++;
    }
}
