<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Domain\Contracts\EmailVerificationTokenRepository;
use NetCode\Identity\Domain\EmailVerificationToken;
use NetCode\Identity\Domain\ValueObjects\EmailVerificationTokenId;

final class InMemoryEmailVerificationTokenRepository implements EmailVerificationTokenRepository
{
    /** @var array<string, EmailVerificationToken> */
    private array $tokens = [];

    public function nextId(): EmailVerificationTokenId
    {
        return EmailVerificationTokenId::random();
    }

    public function findByHash(string $tokenHash): EmailVerificationToken|null
    {
        foreach ($this->tokens as $token) {
            if ($token->tokenHash() === $tokenHash) {
                return $token;
            }
        }

        return null;
    }

    public function save(EmailVerificationToken $token): void
    {
        $this->tokens[$token->id()->value()] = $token;
    }

    public function count(): int
    {
        return count($this->tokens);
    }
}
