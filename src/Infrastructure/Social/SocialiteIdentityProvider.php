<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Social;

use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use NetCode\Identity\Application\Ports\SocialIdentityProvider;
use NetCode\Identity\Application\Ports\SocialProfile;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\Email;
use RuntimeException;

final readonly class SocialiteIdentityProvider implements SocialIdentityProvider
{
    public function __construct(
        private Factory $socialite,
    ) {}

    public function fetch(SocialProvider $provider, string $accessToken): SocialProfile
    {
        $driver = $this->socialite->driver($provider->value);

        if (! $driver instanceof AbstractProvider) {
            throw new RuntimeException("Social provider [{$provider->value}] does not support token authentication.");
        }

        $user = $driver->userFromToken($accessToken);

        return new SocialProfile(
            provider: $provider,
            providerId: (string) $user->getId(),
            email: new Email((string) $user->getEmail()),
            emailVerified: $this->verified($provider, $user),
            name: $user->getName(),
        );
    }

    private function verified(SocialProvider $provider, SocialiteUser $user): bool
    {
        return match ($provider) {
            // Facebook only ever returns a verified email; Google exposes an explicit flag.
            SocialProvider::Facebook => $user->getEmail() !== null,
            SocialProvider::Google => ($user->getRaw()['email_verified'] ?? false) === true,
        };
    }
}
