<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\LogoutAll\LogoutAll;
use Symfony\Component\HttpFoundation\Response;

final readonly class LogoutAllController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->bus->dispatch(new LogoutAll);

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
