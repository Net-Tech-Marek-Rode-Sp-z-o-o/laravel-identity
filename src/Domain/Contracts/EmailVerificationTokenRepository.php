<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Contracts;

use NetCode\Identity\Domain\EmailVerificationToken;
use NetCode\Identity\Domain\ValueObjects\EmailVerificationTokenId;

interface EmailVerificationTokenRepository
{
    public function nextId(): EmailVerificationTokenId;

    public function findByHash(string $tokenHash): EmailVerificationToken|null;

    public function save(EmailVerificationToken $token): void;
}
