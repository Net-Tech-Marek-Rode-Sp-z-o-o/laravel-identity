<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Response;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\RevokeInvitation\RevokeInvitation;

final readonly class RevokeInvitationController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        string $invitationId,
    ): Response {
        $this->bus->dispatch(new RevokeInvitation(
            invitationId: $invitationId,
        ));

        return new Response(status: 204);
    }
}
