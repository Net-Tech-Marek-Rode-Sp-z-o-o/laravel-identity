<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain;

use DateTimeImmutable;
use NetCode\Domain\AggregateRoot;
use NetCode\Identity\Domain\Exception\InvalidResetTokenException;
use NetCode\Identity\Domain\ValueObjects\PasswordResetTokenId;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class PasswordResetToken extends AggregateRoot
{
    private function __construct(
        private readonly PasswordResetTokenId $id,
        private readonly UserId $userId,
        private readonly string $tokenHash,
        private readonly DateTimeImmutable $expiresAt,
        private DateTimeImmutable|null $usedAt,
    ) {}

    public static function issue(
        PasswordResetTokenId $id,
        UserId $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            usedAt: null,
        );
    }

    public static function reconstitute(
        PasswordResetTokenId $id,
        UserId $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable|null $usedAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            usedAt: $usedAt,
        );
    }

    /** @throws InvalidResetTokenException */
    public function redeem(DateTimeImmutable $now): void
    {
        if ($this->usedAt !== null) {
            throw InvalidResetTokenException::alreadyUsed();
        }

        if ($now > $this->expiresAt) {
            throw InvalidResetTokenException::expired();
        }

        $this->usedAt = $now;
    }

    public function id(): PasswordResetTokenId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function usedAt(): DateTimeImmutable|null
    {
        return $this->usedAt;
    }
}
