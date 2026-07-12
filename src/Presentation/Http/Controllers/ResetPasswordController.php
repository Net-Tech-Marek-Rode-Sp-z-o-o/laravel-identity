<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Response;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\ResetPassword\ResetPassword;
use NetCode\Identity\Presentation\Http\Data\ResetPasswordData;

final readonly class ResetPasswordController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        ResetPasswordData $data,
    ): Response {
        $this->bus->dispatch(new ResetPassword(
            token: $data->token,
            password: $data->password,
        ));

        return new Response(status: 204);
    }
}
