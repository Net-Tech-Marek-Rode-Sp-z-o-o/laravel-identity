<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\RevokeInvitation\RevokeInvitation;
use NetCode\Identity\Presentation\Http\Data\RevokeInvitationData;
use Symfony\Component\HttpFoundation\Response;

final readonly class RevokeInvitationController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        RevokeInvitationData $data,
    ): JsonResponse {
        $this->bus->dispatch(new RevokeInvitation(
            invitationId: $data->invitationId,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
