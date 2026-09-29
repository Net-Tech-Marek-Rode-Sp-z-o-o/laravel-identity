<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\DeleteAccount\DeleteAccount;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Presentation\Http\Data\DeleteAccountData;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteAccountController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(
        DeleteAccountData $data,
    ): JsonResponse {
        $this->bus->dispatch(new DeleteAccount(
            userId: UserId::fromString(value: $this->currentUser->user()->id),
            password: $data->password,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
