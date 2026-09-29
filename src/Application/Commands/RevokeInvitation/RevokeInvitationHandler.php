<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\RevokeInvitation;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Domain\Contracts\InvitationRepository;
use NetCode\Identity\Domain\Exceptions\InvalidInvitationException;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Kit\Clock;

final readonly class RevokeInvitationHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private InvitationRepository $invitations,
    ) {}

    public function __invoke(
        RevokeInvitation $command,
    ): void {
        $invitation = $this->invitations->findById(InvitationId::fromString($command->invitationId));

        if ($invitation === null) {
            throw InvalidInvitationException::notFound();
        }

        $invitation->revoke($this->clock->now());
        $this->invitations->save($invitation);
    }
}
