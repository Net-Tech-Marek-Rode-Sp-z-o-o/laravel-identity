<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\InviteUser\InviteUser;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Presentation\Http\Data\InviteUserData;
use NetCode\Identity\Presentation\Http\Resources\InvitationResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class InviteUserController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(
        InviteUserData $data,
    ): JsonResponse {
        $invitation = $this->bus->dispatch(new InviteUser(
            email: $data->email,
            invitedBy: $this->currentUser->user()->id,
        ));

        return new InvitationResource($invitation)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
