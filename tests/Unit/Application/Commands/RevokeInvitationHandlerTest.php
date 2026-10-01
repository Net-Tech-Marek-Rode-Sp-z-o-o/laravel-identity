<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use DateTimeImmutable;
use NetCode\Identity\Application\Commands\RevokeInvitation\RevokeInvitation;
use NetCode\Identity\Application\Commands\RevokeInvitation\RevokeInvitationHandler;
use NetCode\Identity\Domain\Exceptions\InvitationNotFoundException;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\Invitation\InviterOnlyInvitationAccess;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryInvitationRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RevokeInvitationHandlerTest extends TestCase
{
    private const string INVITER_ID = '33333333-3333-4333-8333-333333333333';

    private const string OTHER_ID = '44444444-4444-4444-8444-444444444444';

    private InMemoryInvitationRepository $invitations;

    protected function setUp(): void
    {
        $this->invitations = new InMemoryInvitationRepository;
    }

    #[Test]
    public function it_lets_the_inviter_revoke_the_invitation(): void
    {
        $invitation = $this->seed();

        $this->handler()(new RevokeInvitation(invitationId: $invitation->id()->value(), requestedBy: self::INVITER_ID));

        $this->assertNotNull($this->invitations->findById($invitation->id())?->revokedAt());
    }

    #[Test]
    public function it_hides_the_invitation_from_another_user(): void
    {
        $invitation = $this->seed();

        try {
            $this->handler()(new RevokeInvitation(invitationId: $invitation->id()->value(), requestedBy: self::OTHER_ID));
            $this->fail('Expected an InvitationNotFoundException.');
        } catch (InvitationNotFoundException) {
            $this->assertNull($this->invitations->findById($invitation->id())?->revokedAt());
        }
    }

    #[Test]
    public function it_hides_an_unknown_invitation(): void
    {
        $this->expectException(InvitationNotFoundException::class);

        $this->handler()(new RevokeInvitation(invitationId: InvitationId::random()->value(), requestedBy: self::INVITER_ID));
    }

    private function handler(): RevokeInvitationHandler
    {
        return new RevokeInvitationHandler(
            clock: new FixedClock,
            access: new InviterOnlyInvitationAccess,
            invitations: $this->invitations,
        );
    }

    private function seed(): Invitation
    {
        $invitation = Invitation::issue(
            id: InvitationId::random(),
            realmId: null,
            email: new Email('bob@example.test'),
            invitedBy: UserId::fromString(self::INVITER_ID),
            tokenHash: hash('sha256', 'the-token'),
            metadata: [],
            expiresAt: new DateTimeImmutable('2026-01-08T00:00:00+00:00'),
        );
        $this->invitations->save($invitation);

        return $invitation;
    }
}
