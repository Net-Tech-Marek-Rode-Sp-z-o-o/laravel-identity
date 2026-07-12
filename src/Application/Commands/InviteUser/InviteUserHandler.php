<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\InviteUser;

use DateInterval;
use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\InvitationNotifier;
use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Application\Ports\TokenGenerator;
use NetCode\Identity\Domain\Contracts\InvitationRepository;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Kit\Clock;

final readonly class InviteUserHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RealmContext $realm,
        private TokenGenerator $generator,
        private UserRepository $users,
        private InvitationNotifier $notifier,
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
            tokenHash: hash('sha256', $token),
            metadata: $command->metadata,
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
