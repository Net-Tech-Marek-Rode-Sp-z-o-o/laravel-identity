<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain;

use DateTimeImmutable;
use NetCode\Domain\AggregateRoot;
use NetCode\Identity\Domain\Event\PasswordChanged;
use NetCode\Identity\Domain\Event\UserDeleted;
use NetCode\Identity\Domain\Event\UserRegistered;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class User extends AggregateRoot
{
    private function __construct(
        private readonly UserId $id,
        private readonly RealmId|null $realmId,
        private readonly Email $email,
        private string $name,
        private string|null $passwordHash,
        private DateTimeImmutable|null $deletedAt,
    ) {}

    public static function register(
        UserId $id,
        RealmId|null $realmId,
        Email $email,
        string $name,
        string $passwordHash,
        DateTimeImmutable $now,
    ): self {
        $user = new self(
            id: $id,
            realmId: $realmId,
            email: $email,
            name: $name,
            passwordHash: $passwordHash,
            deletedAt: null,
        );

        $user->recordThat(new UserRegistered(
            userId: $id,
            occurredOn: $now,
        ));

        return $user;
    }

    public static function reconstitute(
        UserId $id,
        RealmId|null $realmId,
        Email $email,
        string $name,
        string|null $passwordHash,
        DateTimeImmutable|null $deletedAt,
    ): self {
        return new self(
            id: $id,
            realmId: $realmId,
            email: $email,
            name: $name,
            passwordHash: $passwordHash,
            deletedAt: $deletedAt,
        );
    }

    public function changePassword(
        string $passwordHash,
        DateTimeImmutable $now,
    ): void {
        $this->passwordHash = $passwordHash;

        $this->recordThat(new PasswordChanged(
            userId: $this->id,
            occurredOn: $now,
        ));
    }

    public function delete(
        DateTimeImmutable $now,
    ): void {
        $this->deletedAt = $now;

        $this->recordThat(new UserDeleted(
            userId: $this->id,
            occurredOn: $now,
        ));
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function realmId(): RealmId|null
    {
        return $this->realmId;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function passwordHash(): string|null
    {
        return $this->passwordHash;
    }

    public function deletedAt(): DateTimeImmutable|null
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
