<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\InviteUser;

use DateInterval;
use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\InvitationMetadataFactory;
use NetCode\Identity\Application\Ports\InvitationNotifier;
use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Application\Ports\TokenGenerator;
use NetCode\Identity\Application\Ports\TokenHasher;
use NetCode\Identity\Domain\Contracts\InvitationRepository;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Kit\Clock;

final readonly class InviteUserHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RealmContext $realm,
        private TokenGenerator $generator,
        private TokenHasher $tokenHasher,
        private UserRepository $users,
        private InvitationNotifier $notifier,
        private InvitationMetadataFactory $metadata,
        private InvitationRepository $invitations,
        private int $ttlMinutes,
    ) {}

    public function __invoke(
        InviteUser $command,
    ): IssuedInvitation {
        $realm = $this->realm->current();
        $email = new Email($command->email);

        if ($this->users->findByEmail($realm, $email) !== null) {
            throw EmailAlreadyTakenException::for($email);
        }

        $token = $this->generator->generate();

        $invitation = Invitation::issue(
            id: $this->invitations->nextId(),
            realmId: $realm,
            email: $email,
            invitedBy: UserId::fromString($command->invitedBy),
            tokenHash: $this->tokenHasher->hash($token),
            metadata: $this->metadata->for($command->invitedBy),
            expiresAt: $this->clock->now()->add(new DateInterval('PT'.$this->ttlMinutes.'M')),
        );

        $this->invitations->save($invitation);
        $this->notifier->notify($email, $token, $invitation->expiresAt());

        return new IssuedInvitation(
            id: $invitation->id()->value(),
            email: $email->value(),
            expiresAt: $invitation->expiresAt(),
        );
    }
}
