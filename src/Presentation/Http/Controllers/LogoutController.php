<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Response;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\Logout\Logout;

final readonly class LogoutController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(): Response
    {
        $this->bus->dispatch(new Logout);

        return new Response(status: 204);
    }
}
