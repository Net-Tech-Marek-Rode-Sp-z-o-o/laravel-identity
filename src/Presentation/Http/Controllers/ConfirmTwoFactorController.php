<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Response;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\ConfirmTwoFactor\ConfirmTwoFactor;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Presentation\Http\Data\TwoFactorCodeData;

final readonly class ConfirmTwoFactorController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(
        TwoFactorCodeData $data,
    ): Response {
        $this->bus->dispatch(new ConfirmTwoFactor(
            userId: UserId::fromString($this->currentUser->user()->id),
            code: $data->code,
        ));

        return new Response(status: 204);
    }
}
