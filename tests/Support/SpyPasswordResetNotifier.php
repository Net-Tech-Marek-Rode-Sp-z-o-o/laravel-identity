<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use DateTimeImmutable;
use NetCode\Identity\Application\Ports\PasswordResetNotifier;
use NetCode\Identity\Domain\ValueObjects\Email;

final class SpyPasswordResetNotifier implements PasswordResetNotifier
{
    public Email|null $email = null;

    public string|null $token = null;

    public function notify(Email $email, string $token, DateTimeImmutable $expiresAt): void
    {
        $this->email = $email;
        $this->token = $token;
    }
}
