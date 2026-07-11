<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Domain\Contract\PasswordResetTokenRepository;
use NetCode\Identity\Domain\PasswordResetToken;
use NetCode\Identity\Domain\ValueObjects\PasswordResetTokenId;

final class InMemoryPasswordResetTokenRepository implements PasswordResetTokenRepository
{
    /** @var array<string, PasswordResetToken> */
    private array $tokens = [];

    public function nextId(): PasswordResetTokenId
    {
        return PasswordResetTokenId::random();
    }

    public function findByHash(string $tokenHash): PasswordResetToken|null
    {
        foreach ($this->tokens as $token) {
            if ($token->tokenHash() === $tokenHash) {
                return $token;
            }
        }

        return null;
    }

    public function save(PasswordResetToken $token): void
    {
        $this->tokens[$token->id()->value()] = $token;
    }
}
