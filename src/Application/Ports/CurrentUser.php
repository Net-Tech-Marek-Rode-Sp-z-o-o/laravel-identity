<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface CurrentUser
{
    public function user(): AuthenticatedUser;

    public function userOrNull(): AuthenticatedUser|null;

    public function tokenId(): string;
}
