<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain;

use DateTimeImmutable;
use NetCode\Identity\Domain\ValueObjects\LinkedAccountId;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class LinkedAccount
{
    private function __construct(
        private readonly LinkedAccountId $id,
        private readonly UserId $userId,
        private readonly SocialProvider $provider,
        private readonly string $providerId,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public static function link(
        LinkedAccountId $id,
        UserId $userId,
        SocialProvider $provider,
        string $providerId,
        DateTimeImmutable $now,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            provider: $provider,
            providerId: $providerId,
            createdAt: $now,
        );
    }

    public static function reconstitute(
        LinkedAccountId $id,
        UserId $userId,
        SocialProvider $provider,
        string $providerId,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            provider: $provider,
            providerId: $providerId,
            createdAt: $createdAt,
        );
    }

    public function id(): LinkedAccountId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function provider(): SocialProvider
    {
        return $this->provider;
    }

    public function providerId(): string
    {
        return $this->providerId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
