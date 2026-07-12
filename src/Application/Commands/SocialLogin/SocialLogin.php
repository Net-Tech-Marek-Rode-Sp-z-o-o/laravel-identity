<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\SocialLogin;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Domain\SocialProvider;

/** @implements Command<string> */
#[HandledBy(SocialLoginHandler::class)]
final readonly class SocialLogin implements Command
{
    public function __construct(
        public SocialProvider $provider,
        public string $accessToken,
    ) {}
}
