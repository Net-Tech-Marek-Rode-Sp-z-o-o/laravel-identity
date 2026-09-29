<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\Logout;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\TokenRevoker;

final readonly class LogoutHandler implements CommandHandler
{
    public function __construct(
        private TokenRevoker $tokens,
    ) {}

    public function __invoke(
        Logout $command,
    ): void {
        $this->tokens->revokeCurrent();
    }
}
