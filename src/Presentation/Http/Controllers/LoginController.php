<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Command\Login\Login;
use NetCode\Identity\Presentation\Http\Data\LoginData;

final readonly class LoginController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        LoginData $data,
    ): JsonResponse {
        $token = $this->bus->dispatch(new Login(
            email: $data->email,
            password: $data->password,
        ));

        return new JsonResponse(['token' => $token]);
    }
}
