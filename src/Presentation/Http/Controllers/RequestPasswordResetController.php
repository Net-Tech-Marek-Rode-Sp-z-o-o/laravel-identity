<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\RequestPasswordReset\RequestPasswordReset;
use NetCode\Identity\Presentation\Http\Data\RequestPasswordResetData;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequestPasswordResetController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        RequestPasswordResetData $data,
    ): JsonResponse {
        $this->bus->dispatch(new RequestPasswordReset(
            email: $data->email,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
