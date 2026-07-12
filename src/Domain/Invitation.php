<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain;

use DateTimeImmutable;
use NetCode\Domain\AggregateRoot;
use NetCode\Identity\Domain\Events\InvitationAccepted;
use NetCode\Identity\Domain\Exceptions\InvalidInvitationException;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Identity\Domain\ValueObjects\RealmId;

final class Invitation extends AggregateRoot
{
    /** @param array<string, mixed> $metadata */
    private function __construct(
        private readonly InvitationId $id,
        private readonly RealmId|null $realmId,
        private readonly Email $email,
        private readonly string $tokenHash,
        private readonly array $metadata,
        private readonly DateTimeImmutable $expiresAt,
        private DateTimeImmutable|null $acceptedAt,
        private DateTimeImmutable|null $revokedAt,
    ) {}

    /** @param array<string, mixed> $metadata */
    public static function issue(
        InvitationId $id,
        RealmId|null $realmId,
        Email $email,
        string $tokenHash,
        array $metadata,
        DateTimeImmutable $expiresAt,
    ): self {
        return new self(
            id: $id,
            realmId: $realmId,
            email: $email,
            tokenHash: $tokenHash,
            metadata: $metadata,
            expiresAt: $expiresAt,
            acceptedAt: null,
            revokedAt: null,
        );
    }

    /** @param array<string, mixed> $metadata */
    public static function reconstitute(
        InvitationId $id,
        RealmId|null $realmId,
        Email $email,
        string $tokenHash,
        array $metadata,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable|null $acceptedAt,
        DateTimeImmutable|null $revokedAt,
    ): self {
        return new self(
            id: $id,
            realmId: $realmId,
            email: $email,
            tokenHash: $tokenHash,
            metadata: $metadata,
            expiresAt: $expiresAt,
            acceptedAt: $acceptedAt,
            revokedAt: $revokedAt,
        );
    }

    /** @throws InvalidInvitationException */
    public function accept(DateTimeImmutable $now): void
    {
        if ($this->revokedAt !== null) {
            throw InvalidInvitationException::revoked();
        }

        if ($this->acceptedAt !== null) {
            throw InvalidInvitationException::alreadyAccepted();
        }

        if ($now > $this->expiresAt) {
            throw InvalidInvitationException::expired();
        }

        $this->acceptedAt = $now;

        $this->recordThat(new InvitationAccepted(
            invitationId: $this->id,
            occurredOn: $now,
        ));
    }

    /** @throws InvalidInvitationException */
    public function revoke(DateTimeImmutable $now): void
    {
        if ($this->acceptedAt !== null) {
            throw InvalidInvitationException::alreadyAccepted();
        }

        $this->revokedAt = $now;
    }

    public function id(): InvitationId
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

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function acceptedAt(): DateTimeImmutable|null
    {
        return $this->acceptedAt;
    }

    public function revokedAt(): DateTimeImmutable|null
    {
        return $this->revokedAt;
    }
}
