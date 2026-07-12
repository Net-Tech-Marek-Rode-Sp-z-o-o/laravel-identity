<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\LogoutAll;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\TokenRevoker;

final readonly class LogoutAllHandler implements CommandHandler
{
    public function __construct(
        private TokenRevoker $tokens,
    ) {}

    public function __invoke(
        LogoutAll $command,
    ): null {
        $this->tokens->revokeAll();

        return null;
    }
}
