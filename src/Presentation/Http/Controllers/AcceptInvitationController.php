<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\AcceptInvitation\AcceptInvitation;
use NetCode\Identity\Presentation\Http\Data\AcceptInvitationData;
use NetCode\Identity\Presentation\Http\Resources\RegisteredUserResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class AcceptInvitationController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        AcceptInvitationData $data,
    ): JsonResponse {
        $userId = $this->bus->dispatch(new AcceptInvitation(
            token: $data->token,
            name: $data->name,
            password: $data->password,
        ));

        return new RegisteredUserResource($userId)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
