<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\TokenRevoker;

final class RecordingTokenRevoker implements TokenRevoker
{
    public bool $currentRevoked = false;

    public bool $allRevoked = false;

    public function revokeCurrent(): void
    {
        $this->currentRevoked = true;
    }

    public function revokeAll(): void
    {
        $this->allRevoked = true;
    }
}
