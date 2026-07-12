<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\SocialIdentityProvider;
use NetCode\Identity\Application\Ports\SocialProfile;
use NetCode\Identity\Domain\SocialProvider;

final class FakeSocialIdentityProvider implements SocialIdentityProvider
{
    public function __construct(
        public SocialProfile $profile,
    ) {}

    public function fetch(SocialProvider $provider, string $accessToken): SocialProfile
    {
        return $this->profile;
    }
}
