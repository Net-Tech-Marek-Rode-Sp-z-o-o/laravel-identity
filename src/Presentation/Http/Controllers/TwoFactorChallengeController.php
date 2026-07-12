<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Commands\VerifyTwoFactorChallenge\VerifyTwoFactorChallenge;
use NetCode\Identity\Presentation\Http\Data\TwoFactorChallengeData;
use NetCode\Identity\Presentation\Http\Resources\TokenResource;

final readonly class TwoFactorChallengeController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        TwoFactorChallengeData $data,
    ): TokenResource {
        $token = $this->bus->dispatch(new VerifyTwoFactorChallenge(
            challengeToken: $data->challengeToken,
            code: $data->code,
        ));

        return new TokenResource($token);
    }
}
