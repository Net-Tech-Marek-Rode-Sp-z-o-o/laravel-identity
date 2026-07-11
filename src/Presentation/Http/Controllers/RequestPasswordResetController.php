<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Response;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Command\RequestPasswordReset\RequestPasswordReset;
use NetCode\Identity\Presentation\Http\Data\RequestPasswordResetData;

final readonly class RequestPasswordResetController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        RequestPasswordResetData $data,
    ): Response {
        $this->bus->dispatch(new RequestPasswordReset(
            email: $data->email,
        ));

        return new Response(status: 204);
    }
}
