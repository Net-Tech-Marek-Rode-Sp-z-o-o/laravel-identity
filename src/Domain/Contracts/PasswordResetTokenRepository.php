<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Contracts;

use NetCode\Identity\Domain\PasswordResetToken;
use NetCode\Identity\Domain\ValueObjects\PasswordResetTokenId;

interface PasswordResetTokenRepository
{
    public function nextId(): PasswordResetTokenId;

    public function findByHash(string $tokenHash): PasswordResetToken|null;

    public function save(PasswordResetToken $token): void;
}
