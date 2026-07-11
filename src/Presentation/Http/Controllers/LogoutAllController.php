<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Response;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Command\LogoutAll\LogoutAll;

final readonly class LogoutAllController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(): Response
    {
        $this->bus->dispatch(new LogoutAll);

        return new Response(status: 204);
    }
}
