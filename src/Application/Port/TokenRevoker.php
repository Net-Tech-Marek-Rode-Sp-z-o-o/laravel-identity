<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

interface TokenRevoker
{
    public function revokeCurrent(): void;

    public function revokeAll(): void;
}
