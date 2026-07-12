<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\Email;

final readonly class SocialProfile
{
    public function __construct(
        public SocialProvider $provider,
        public string $providerId,
        public Email $email,
        public bool $emailVerified,
        public string|null $name,
    ) {}
}
