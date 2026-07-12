<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

use NetCode\Identity\Domain\SocialProvider;

interface SocialIdentityProvider
{
    public function fetch(SocialProvider $provider, string $accessToken): SocialProfile;
}
