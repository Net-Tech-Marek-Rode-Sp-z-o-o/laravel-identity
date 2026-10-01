<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\RevokeInvitation;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\InvitationAccess;
use NetCode\Identity\Application\Ports\InvitationSnapshot;
use NetCode\Identity\Domain\Contracts\InvitationRepository;
use NetCode\Identity\Domain\Exceptions\InvitationNotFoundException;
use NetCode\Identity\Domain\Invitation;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Kit\Clock;

final readonly class RevokeInvitationHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private InvitationAccess $access,
        private InvitationRepository $invitations,
    ) {}

    public function __invoke(
        RevokeInvitation $command,
    ): void {
        $invitation = $this->invitations->findById(InvitationId::fromString($command->invitationId));

        if ($invitation === null || ! $this->access->allowsRevoke($command->requestedBy, $this->snapshotOf($invitation))) {
            throw InvitationNotFoundException::withId($command->invitationId);
        }

        $invitation->revoke($this->clock->now());
        $this->invitations->save($invitation);
    }

    private function snapshotOf(Invitation $invitation): InvitationSnapshot
    {
        return new InvitationSnapshot(
            id: $invitation->id()->value(),
            invitedBy: $invitation->invitedBy()?->value(),
            metadata: $invitation->metadata(),
        );
    }
}
