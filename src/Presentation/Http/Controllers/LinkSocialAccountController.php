<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\LinkSocialAccount\LinkSocialAccount;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Presentation\Http\Data\SocialAuthData;
use Symfony\Component\HttpFoundation\Response;

final readonly class LinkSocialAccountController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(
        SocialAuthData $data,
    ): JsonResponse {
        $this->bus->dispatch(new LinkSocialAccount(
            userId: UserId::fromString($this->currentUser->user()->id),
            provider: SocialProvider::tryFrom($data->provider) ?? abort(Response::HTTP_NOT_FOUND),
            accessToken: $data->accessToken,
        ));

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }
}
