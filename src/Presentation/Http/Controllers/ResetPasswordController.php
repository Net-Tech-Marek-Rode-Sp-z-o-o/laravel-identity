<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\ResetPassword\ResetPassword;
use NetCode\Identity\Presentation\Http\Data\ResetPasswordData;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResetPasswordController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        ResetPasswordData $data,
    ): JsonResponse {
        $this->bus->dispatch(new ResetPassword(
            token: $data->token,
            password: $data->password,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
