<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\Logout\Logout;
use NetCode\Identity\Application\Commands\Logout\LogoutHandler;
use NetCode\Identity\Application\Commands\LogoutAll\LogoutAll;
use NetCode\Identity\Application\Commands\LogoutAll\LogoutAllHandler;
use NetCode\Identity\Tests\Support\RecordingTokenRevoker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogoutHandlerTest extends TestCase
{
    #[Test]
    public function logout_revokes_the_current_token(): void
    {
        $revoker = new RecordingTokenRevoker;

        (new LogoutHandler($revoker))(new Logout);

        $this->assertTrue($revoker->currentRevoked);
        $this->assertFalse($revoker->allRevoked);
    }

    #[Test]
    public function logout_all_revokes_every_token(): void
    {
        $revoker = new RecordingTokenRevoker;

        (new LogoutAllHandler($revoker))(new LogoutAll);

        $this->assertTrue($revoker->allRevoked);
        $this->assertFalse($revoker->currentRevoked);
    }
}
