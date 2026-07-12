<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\SocialLogin\SocialLogin;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Presentation\Http\Data\SocialTokenData;
use NetCode\Identity\Presentation\Http\Resources\TokenResource;

final readonly class SocialLoginController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        string $provider,
        SocialTokenData $data,
    ): TokenResource {
        $token = $this->bus->dispatch(new SocialLogin(
            provider: SocialProvider::tryFrom($provider) ?? abort(404),
            accessToken: $data->accessToken,
        ));

        return new TokenResource($token);
    }
}
