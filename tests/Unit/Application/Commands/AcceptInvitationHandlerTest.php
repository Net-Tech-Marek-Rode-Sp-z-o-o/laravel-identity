<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use DateTimeImmutable;
use NetCode\Identity\Application\Commands\AcceptInvitation\AcceptInvitation;
use NetCode\Identity\Application\Commands\AcceptInvitation\AcceptInvitationHandler;
use NetCode\Identity\Domain\Exceptions\InvalidInvitationException;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\Security\Sha256TokenHasher;
use NetCode\Identity\Tests\Support\FakePasswordHasher;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryInvitationRepository;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\RecordingInvitationAcceptanceHook;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AcceptInvitationHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryInvitationRepository $invitations;

    private RecordingInvitationAcceptanceHook $hook;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository;
        $this->invitations = new InMemoryInvitationRepository;
        $this->hook = new RecordingInvitationAcceptanceHook;
    }

    private function handler(): AcceptInvitationHandler
    {
        return new AcceptInvitationHandler(
            tokenHasher: new Sha256TokenHasher,
            clock: new FixedClock,
            hasher: new FakePasswordHasher,
            users: $this->users,
            invitations: $this->invitations,
            hook: $this->hook,
        );
    }

    /** @param array<string, mixed> $metadata */
    private function seed(string $expiresAt = '2026-01-08T00:00:00+00:00', array $metadata = ['role' => 'admin']): void
    {
        $this->invitations->save(Invitation::issue(
            id: InvitationId::random(),
            realmId: null,
            email: new Email('ada@example.test'),
            tokenHash: hash('sha256', 'the-token'),
            metadata: $metadata,
            expiresAt: new DateTimeImmutable($expiresAt),
        ));
    }

    #[Test]
    public function it_creates_a_user_marks_the_invitation_accepted_and_runs_the_hook(): void
    {
        $this->seed();

        $userId = ($this->handler())(new AcceptInvitation(
            token: 'the-token',
            name: 'Ada',
            password: 'new-password',
        ));

        $user = $this->users->getById(UserId::fromString($userId));
        $this->assertSame('ada@example.test', $user->email()->value());
        $this->assertSame('hashed:new-password', $user->passwordHash());
        $this->assertTrue($user->isEmailVerified());
        $this->assertNotNull($this->invitations->findByHash(hash('sha256', 'the-token'))?->acceptedAt());
        $this->assertSame(['role' => 'admin'], $this->hook->accepted?->metadata);
        $this->assertSame($userId, $this->hook->accepted?->userId);
    }

    #[Test]
    public function it_rejects_an_unknown_token(): void
    {
        $this->expectException(InvalidInvitationException::class);

        ($this->handler())(new AcceptInvitation(token: 'nope', name: 'Ada', password: 'new-password'));
    }

    #[Test]
    public function it_rejects_an_expired_invitation(): void
    {
        $this->seed(expiresAt: '2025-12-31T23:00:00+00:00');

        $this->expectException(InvalidInvitationException::class);

        ($this->handler())(new AcceptInvitation(token: 'the-token', name: 'Ada', password: 'new-password'));
    }
}
