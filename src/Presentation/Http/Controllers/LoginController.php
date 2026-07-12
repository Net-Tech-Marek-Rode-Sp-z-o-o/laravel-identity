<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\Login\Login;
use NetCode\Identity\Presentation\Http\Data\LoginData;
use NetCode\Identity\Presentation\Http\Resources\LoginResource;

final readonly class LoginController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        LoginData $data,
    ): LoginResource {
        $result = $this->bus->dispatch(new Login(
            email: $data->email,
            password: $data->password,
        ));

        return new LoginResource($result);
    }
}
