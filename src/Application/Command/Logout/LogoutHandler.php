<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\Logout;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Port\TokenRevoker;

final readonly class LogoutHandler implements CommandHandler
{
    public function __construct(
        private TokenRevoker $tokens,
    ) {}

    public function __invoke(
        Logout $command,
    ): null {
        $this->tokens->revokeCurrent();

        return null;
    }
}
