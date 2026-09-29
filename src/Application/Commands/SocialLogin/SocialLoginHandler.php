<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\SocialLogin;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Application\Ports\SocialIdentityProvider;
use NetCode\Identity\Application\Ports\SocialProfile;
use NetCode\Identity\Application\Ports\TokenIssuer;
use NetCode\Identity\Domain\Contracts\LinkedAccountRepository;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\SocialEmailNotVerifiedException;
use NetCode\Identity\Domain\LinkedAccount;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Kit\Clock;

final readonly class SocialLoginHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RealmContext $realm,
        private TokenIssuer $tokens,
        private UserRepository $users,
        private LinkedAccountRepository $links,
        private SocialIdentityProvider $social,
    ) {}

    public function __invoke(
        SocialLogin $command,
    ): string {
        $profile = $this->social->fetch($command->provider, $command->accessToken);

        $linked = $this->links->findByProvider($profile->provider, $profile->providerId);
        if ($linked !== null) {
            return $this->tokens->issue($linked->userId());
        }

        $realm = $this->realm->current();
        $existing = $this->users->findByEmail($realm, $profile->email);

        if ($existing !== null) {
            if (! $profile->emailVerified) {
                throw SocialEmailNotVerifiedException::create();
            }

            if (! $existing->isEmailVerified()) {
                $existing->verifyEmail(now: $this->clock->now());
                $this->users->save(user: $existing);
            }

            $this->link($existing->id(), $profile);

            return $this->tokens->issue($existing->id());
        }

        $user = User::registerPasswordless(
            id: $this->users->nextId(),
            realmId: $realm,
            email: $profile->email,
            name: $profile->name ?? $profile->email->value(),
            now: $this->clock->now(),
        );

        if ($profile->emailVerified) {
            $user->verifyEmail(now: $this->clock->now());
        }

        $this->users->save($user);
        $this->link($user->id(), $profile);

        return $this->tokens->issue($user->id());
    }

    private function link(UserId $userId, SocialProfile $profile): void
    {
        $this->links->save(LinkedAccount::link(
            id: $this->links->nextId(),
            userId: $userId,
            provider: $profile->provider,
            providerId: $profile->providerId,
            now: $this->clock->now(),
        ));
    }
}
