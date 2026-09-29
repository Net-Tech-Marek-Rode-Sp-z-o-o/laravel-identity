<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\VerifyEmail\VerifyEmail;
use NetCode\Identity\Presentation\Http\Data\VerifyEmailData;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyEmailController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        VerifyEmailData $data,
    ): JsonResponse {
        $this->bus->dispatch(new VerifyEmail(
            token: $data->token,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
