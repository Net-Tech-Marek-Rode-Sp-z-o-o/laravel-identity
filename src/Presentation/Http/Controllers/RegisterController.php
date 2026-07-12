<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\Register\Register;
use NetCode\Identity\Presentation\Http\Data\RegisterData;
use NetCode\Identity\Presentation\Http\Resources\RegisteredUserResource;

final readonly class RegisterController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        RegisterData $data,
    ): JsonResponse {
        $userId = $this->bus->dispatch(new Register(
            name: $data->name,
            email: $data->email,
            password: $data->password,
        ));

        return new RegisteredUserResource($userId)->response()->setStatusCode(201);
    }
}
