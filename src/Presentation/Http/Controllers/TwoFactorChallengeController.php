<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Command\VerifyTwoFactorChallenge\VerifyTwoFactorChallenge;
use NetCode\Identity\Presentation\Http\Data\TwoFactorChallengeData;

final readonly class TwoFactorChallengeController
{
    public function __construct(
        private CommandBus $bus,
    ) {}

    public function __invoke(
        TwoFactorChallengeData $data,
    ): JsonResponse {
        $token = $this->bus->dispatch(new VerifyTwoFactorChallenge(
            challengeToken: $data->challengeToken,
            code: $data->code,
        ));

        return new JsonResponse(['token' => $token]);
    }
}
