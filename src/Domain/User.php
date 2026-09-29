<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain;

use DateTimeImmutable;
use NetCode\Domain\AggregateRoot;
use NetCode\Identity\Domain\Events\EmailVerified;
use NetCode\Identity\Domain\Events\PasswordChanged;
use NetCode\Identity\Domain\Events\TwoFactorDisabled;
use NetCode\Identity\Domain\Events\TwoFactorEnabled;
use NetCode\Identity\Domain\Events\UserDeleted;
use NetCode\Identity\Domain\Events\UserRegistered;
use NetCode\Identity\Domain\Exceptions\TwoFactorNotEnrolledException;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\TwoFactorSettings;
use NetCode\Identity\Domain\ValueObjects\UserId;

final class User extends AggregateRoot
{
    private function __construct(
        private readonly UserId $id,
        private readonly RealmId|null $realmId,
        private readonly Email $email,
        private string $name,
        private string|null $passwordHash,
        private TwoFactorSettings|null $twoFactor,
        private DateTimeImmutable|null $deletedAt,
        private DateTimeImmutable|null $emailVerifiedAt,
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
            twoFactor: null,
            deletedAt: null,
            emailVerifiedAt: null,
        );

        $user->recordThat(new UserRegistered(
            userId: $id,
            occurredOn: $now,
        ));

        return $user;
    }

    public static function registerPasswordless(
        UserId $id,
        RealmId|null $realmId,
        Email $email,
        string $name,
        DateTimeImmutable $now,
    ): self {
        $user = new self(
            id: $id,
            realmId: $realmId,
            email: $email,
            name: $name,
            passwordHash: null,
            twoFactor: null,
            deletedAt: null,
            emailVerifiedAt: null,
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
        TwoFactorSettings|null $twoFactor,
        DateTimeImmutable|null $deletedAt,
        DateTimeImmutable|null $emailVerifiedAt = null,
    ): self {
        return new self(
            id: $id,
            realmId: $realmId,
            email: $email,
            name: $name,
            passwordHash: $passwordHash,
            twoFactor: $twoFactor,
            deletedAt: $deletedAt,
            emailVerifiedAt: $emailVerifiedAt,
        );
    }

    public function verifyEmail(DateTimeImmutable $now): void
    {
        if ($this->emailVerifiedAt !== null) {
            return;
        }

        $this->emailVerifiedAt = $now;

        $this->recordThat(new EmailVerified(
            userId: $this->id,
            occurredOn: $now,
        ));
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

    public function enableTwoFactor(TwoFactorSettings $settings): void
    {
        $this->twoFactor = $settings;
    }

    /** @throws TwoFactorNotEnrolledException */
    public function confirmTwoFactor(DateTimeImmutable $at): void
    {
        if ($this->twoFactor === null) {
            throw TwoFactorNotEnrolledException::create();
        }

        $this->twoFactor = $this->twoFactor->confirm($at);

        $this->recordThat(new TwoFactorEnabled(
            userId: $this->id,
            occurredOn: $at,
        ));
    }

    /** @throws TwoFactorNotEnrolledException */
    public function disableTwoFactor(DateTimeImmutable $now): void
    {
        if ($this->twoFactor === null) {
            throw TwoFactorNotEnrolledException::create();
        }

        $this->twoFactor = null;

        $this->recordThat(new TwoFactorDisabled(
            userId: $this->id,
            occurredOn: $now,
        ));
    }

    /**
     * @param list<string> $hashedCodes
     *
     * @throws TwoFactorNotEnrolledException
     */
    public function regenerateRecoveryCodes(array $hashedCodes): void
    {
        if ($this->twoFactor === null || ! $this->twoFactor->isConfirmed()) {
            throw TwoFactorNotEnrolledException::create();
        }

        $this->twoFactor = $this->twoFactor->withRecoveryCodes($hashedCodes);
    }

    /** @throws TwoFactorNotEnrolledException */
    public function consumeRecoveryCode(string $hashedCode): void
    {
        if ($this->twoFactor === null) {
            throw TwoFactorNotEnrolledException::create();
        }

        $this->twoFactor = $this->twoFactor->withoutRecoveryCode($hashedCode);
    }

    public function hasActiveTwoFactor(): bool
    {
        return $this->twoFactor !== null && $this->twoFactor->isConfirmed();
    }

    public function hasRecoveryCode(string $hashedCode): bool
    {
        return $this->twoFactor !== null && $this->twoFactor->hasRecoveryCode($hashedCode);
    }

    public function twoFactor(): TwoFactorSettings|null
    {
        return $this->twoFactor;
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

    public function emailVerifiedAt(): DateTimeImmutable|null
    {
        return $this->emailVerifiedAt;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
