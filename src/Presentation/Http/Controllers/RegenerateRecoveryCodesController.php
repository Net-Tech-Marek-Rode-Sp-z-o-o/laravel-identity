<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Command\RegenerateRecoveryCodes\RegenerateRecoveryCodes;
use NetCode\Identity\Application\Port\CurrentUser;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Presentation\Http\Resource\RecoveryCodesResource;

final readonly class RegenerateRecoveryCodesController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(): JsonResponse
    {
        $codes = $this->bus->dispatch(new RegenerateRecoveryCodes(
            userId: UserId::fromString($this->currentUser->user()->id),
        ));

        return new JsonResponse(RecoveryCodesResource::fromRecoveryCodes($codes));
    }
}
