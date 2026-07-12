<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\InviteUser\InviteUser;
use NetCode\Identity\Presentation\Http\Data\InviteUserData;
use NetCode\Identity\Presentation\Http\Resources\InvitationResource;

final readonly class InviteUserController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        InviteUserData $data,
    ): JsonResponse {
        $invitation = $this->bus->dispatch(new InviteUser(
            email: $data->email,
            metadata: $data->metadata,
        ));

        return new InvitationResource($invitation)->response()->setStatusCode(201);
    }
}
