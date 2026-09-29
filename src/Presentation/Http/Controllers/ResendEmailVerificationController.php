<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\ResendEmailVerification\ResendEmailVerification;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Domain\ValueObjects\UserId;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResendEmailVerificationController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->bus->dispatch(new ResendEmailVerification(
            userId: UserId::fromString(value: $this->currentUser->user()->id),
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
