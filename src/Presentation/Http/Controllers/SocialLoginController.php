<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\SocialLogin\SocialLogin;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Presentation\Http\Data\SocialAuthData;
use NetCode\Identity\Presentation\Http\Resources\TokenResource;
use Symfony\Component\HttpFoundation\Response;

final readonly class SocialLoginController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        SocialAuthData $data,
    ): TokenResource {
        $token = $this->bus->dispatch(new SocialLogin(
            provider: SocialProvider::tryFrom($data->provider) ?? abort(Response::HTTP_NOT_FOUND),
            accessToken: $data->accessToken,
        ));

        return new TokenResource($token);
    }
}
