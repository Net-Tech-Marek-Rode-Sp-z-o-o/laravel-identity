<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Domain;

use DateTimeImmutable;
use NetCode\Identity\Domain\Events\InvitationAccepted;
use NetCode\Identity\Domain\Exceptions\InvalidInvitationException;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvitationTest extends TestCase
{
    private const string NOW = '2026-01-01T00:00:00+00:00';

    /** @param array<string, mixed> $metadata */
    private function invitation(string $expiresAt = '2026-01-08T00:00:00+00:00', array $metadata = [], bool $withInviter = true): Invitation
    {
        return Invitation::issue(
            id: InvitationId::random(),
            realmId: null,
            email: new Email('ada@example.test'),
            invitedBy: $withInviter ? UserId::random() : null,
            tokenHash: hash('sha256', 'the-token'),
            metadata: $metadata,
            expiresAt: new DateTimeImmutable($expiresAt),
        );
    }

    #[Test]
    public function it_is_pending_when_issued(): void
    {
        $invitation = $this->invitation();

        $this->assertNull($invitation->acceptedAt());
        $this->assertNull($invitation->revokedAt());
        $this->assertCount(0, $invitation->releaseEvents());
    }

    #[Test]
    public function accepting_marks_it_accepted_and_records_the_event(): void
    {
        $invitation = $this->invitation();

        $invitation->accept(new DateTimeImmutable(self::NOW));

        $this->assertNotNull($invitation->acceptedAt());
        $events = $invitation->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(InvitationAccepted::class, $events[0]);
    }

    #[Test]
    public function it_cannot_be_accepted_twice(): void
    {
        $invitation = $this->invitation();
        $invitation->accept(new DateTimeImmutable(self::NOW));

        $this->expectException(InvalidInvitationException::class);

        $invitation->accept(new DateTimeImmutable(self::NOW));
    }

    #[Test]
    public function an_expired_invitation_cannot_be_accepted(): void
    {
        $invitation = $this->invitation(expiresAt: '2025-12-31T23:00:00+00:00');

        $this->expectException(InvalidInvitationException::class);

        $invitation->accept(new DateTimeImmutable(self::NOW));
    }

    #[Test]
    public function a_revoked_invitation_cannot_be_accepted(): void
    {
        $invitation = $this->invitation();
        $invitation->revoke(new DateTimeImmutable(self::NOW));

        $this->assertNotNull($invitation->revokedAt());
        $this->expectException(InvalidInvitationException::class);

        $invitation->accept(new DateTimeImmutable(self::NOW));
    }

    #[Test]
    public function an_accepted_invitation_cannot_be_revoked(): void
    {
        $invitation = $this->invitation();
        $invitation->accept(new DateTimeImmutable(self::NOW));

        $this->expectException(InvalidInvitationException::class);

        $invitation->revoke(new DateTimeImmutable(self::NOW));
    }

    #[Test]
    public function it_cannot_be_accepted_without_an_inviter(): void
    {
        $invitation = $this->invitation(withInviter: false);

        $this->expectException(InvalidInvitationException::class);

        $invitation->accept(new DateTimeImmutable(self::NOW));
    }
}
