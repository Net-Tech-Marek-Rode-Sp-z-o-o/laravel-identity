<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\Response;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\LinkSocialAccount\LinkSocialAccount;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Presentation\Http\Data\SocialTokenData;

final readonly class LinkSocialAccountController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(
        string $provider,
        SocialTokenData $data,
    ): Response {
        $this->bus->dispatch(new LinkSocialAccount(
            userId: UserId::fromString($this->currentUser->user()->id),
            provider: SocialProvider::tryFrom($provider) ?? abort(404),
            accessToken: $data->accessToken,
        ));

        return new Response(status: 204);
    }
}
