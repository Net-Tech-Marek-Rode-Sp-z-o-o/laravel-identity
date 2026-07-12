<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use DateTimeImmutable;
use NetCode\Identity\Application\Commands\RevokeInvitation\RevokeInvitation;
use NetCode\Identity\Application\Commands\RevokeInvitation\RevokeInvitationHandler;
use NetCode\Identity\Domain\Exceptions\InvalidInvitationException;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryInvitationRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RevokeInvitationHandlerTest extends TestCase
{
    #[Test]
    public function it_revokes_a_pending_invitation(): void
    {
        $id = InvitationId::random();
        $invitations = new InMemoryInvitationRepository;
        $invitations->save(Invitation::issue(
            id: $id,
            realmId: null,
            email: new Email('ada@example.test'),
            tokenHash: hash('sha256', 'the-token'),
            metadata: [],
            expiresAt: new DateTimeImmutable('2026-01-08T00:00:00+00:00'),
        ));

        $handler = new RevokeInvitationHandler(clock: new FixedClock, invitations: $invitations);
        $handler(new RevokeInvitation(invitationId: $id->value()));

        $this->assertNotNull($invitations->findById($id)?->revokedAt());
    }

    #[Test]
    public function it_rejects_an_unknown_invitation(): void
    {
        $handler = new RevokeInvitationHandler(clock: new FixedClock, invitations: new InMemoryInvitationRepository);

        $this->expectException(InvalidInvitationException::class);

        $handler(new RevokeInvitation(invitationId: InvitationId::random()->value()));
    }
}
