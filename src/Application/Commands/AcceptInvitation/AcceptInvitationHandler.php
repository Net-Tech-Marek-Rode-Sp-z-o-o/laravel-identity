<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\AcceptInvitation;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\AcceptedInvitation;
use NetCode\Identity\Application\Ports\InvitationAcceptanceHook;
use NetCode\Identity\Application\Ports\PasswordHasher;
use NetCode\Identity\Domain\Contracts\InvitationRepository;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use NetCode\Identity\Domain\Exceptions\InvalidInvitationException;
use NetCode\Identity\Domain\User;
use NetCode\Kit\Clock;

final readonly class AcceptInvitationHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private PasswordHasher $hasher,
        private UserRepository $users,
        private InvitationRepository $invitations,
        private InvitationAcceptanceHook $hook,
    ) {}

    public function __invoke(
        AcceptInvitation $command,
    ): string {
        $invitation = $this->invitations->findByHash(hash('sha256', $command->token));

        if ($invitation === null) {
            throw InvalidInvitationException::notFound();
        }

        $invitation->accept($this->clock->now());

        if ($this->users->findByEmail($invitation->realmId(), $invitation->email()) !== null) {
            throw EmailAlreadyTakenException::for($invitation->email());
        }

        $user = User::register(
            id: $this->users->nextId(),
            realmId: $invitation->realmId(),
            email: $invitation->email(),
            name: $command->name,
            passwordHash: $this->hasher->hash($command->password),
            now: $this->clock->now(),
        );

        $this->users->save($user);
        $this->invitations->save($invitation);

        $this->hook->afterAcceptance(new AcceptedInvitation(
            userId: $user->id()->value(),
            email: $invitation->email()->value(),
            realmId: $invitation->realmId()?->value(),
            metadata: $invitation->metadata(),
        ));

        return $user->id()->value();
    }
}
