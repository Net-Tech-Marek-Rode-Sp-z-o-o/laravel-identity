<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\DisableTwoFactor\DisableTwoFactor;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Presentation\Http\Data\TwoFactorCodeData;
use Symfony\Component\HttpFoundation\Response;

final readonly class DisableTwoFactorController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(
        TwoFactorCodeData $data,
    ): JsonResponse {
        $this->bus->dispatch(new DisableTwoFactor(
            userId: UserId::fromString($this->currentUser->user()->id),
            code: $data->code,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
