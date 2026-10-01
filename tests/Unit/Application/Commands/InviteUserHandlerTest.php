<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\InviteUser\InviteUser;
use NetCode\Identity\Application\Commands\InviteUser\InviteUserHandler;
use NetCode\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\Security\Sha256TokenHasher;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\FixedInvitationMetadataFactory;
use NetCode\Identity\Tests\Support\FixedRealmContext;
use NetCode\Identity\Tests\Support\FixedTokenGenerator;
use NetCode\Identity\Tests\Support\InMemoryInvitationRepository;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\SpyInvitationNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InviteUserHandlerTest extends TestCase
{
    private const string INVITER_ID = '33333333-3333-4333-8333-333333333333';

    private function handler(
        InMemoryUserRepository $users,
        InMemoryInvitationRepository $invitations,
        SpyInvitationNotifier $notifier,
    ): InviteUserHandler {
        return new InviteUserHandler(
            tokenHasher: new Sha256TokenHasher,
            clock: new FixedClock,
            realm: new FixedRealmContext,
            generator: new FixedTokenGenerator('the-token'),
            users: $users,
            notifier: $notifier,
            invitations: $invitations,
            ttlMinutes: 4320,
            metadata: new FixedInvitationMetadataFactory(['household_id' => 'h-1']),
        );
    }

    #[Test]
    public function it_issues_and_notifies_an_invitation(): void
    {
        $invitations = new InMemoryInvitationRepository;
        $notifier = new SpyInvitationNotifier;

        $issued = ($this->handler(new InMemoryUserRepository, $invitations, $notifier))(new InviteUser(
            email: 'ada@example.test',
            invitedBy: self::INVITER_ID,
        ));

        $this->assertSame('ada@example.test', $issued->email);
        $this->assertSame('the-token', $notifier->token);
        $stored = $invitations->findByHash(hash('sha256', 'the-token'));
        $this->assertSame(self::INVITER_ID, $stored?->invitedBy()?->value());
        $this->assertSame(['household_id' => 'h-1'], $stored->metadata());
    }

    #[Test]
    public function it_rejects_inviting_an_existing_user_email(): void
    {
        $users = new InMemoryUserRepository;
        $users->save(User::reconstitute(
            id: UserId::random(),
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            twoFactor: null,
            deletedAt: null,
        ));

        $this->expectException(EmailAlreadyTakenException::class);

        ($this->handler($users, new InMemoryInvitationRepository, new SpyInvitationNotifier))(new InviteUser(
            email: 'ada@example.test',
            invitedBy: self::INVITER_ID,
        ));
    }
}
