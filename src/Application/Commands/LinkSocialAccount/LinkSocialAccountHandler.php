<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\LinkSocialAccount;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\SocialIdentityProvider;
use NetCode\Identity\Domain\Contracts\LinkedAccountRepository;
use NetCode\Identity\Domain\Exceptions\SocialAccountAlreadyLinkedException;
use NetCode\Identity\Domain\LinkedAccount;
use NetCode\Kit\Clock;

final readonly class LinkSocialAccountHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private LinkedAccountRepository $links,
        private SocialIdentityProvider $social,
    ) {}

    public function __invoke(
        LinkSocialAccount $command,
    ): void {
        $profile = $this->social->fetch($command->provider, $command->accessToken);

        $linked = $this->links->findByProvider($profile->provider, $profile->providerId);
        if ($linked !== null) {
            if ($linked->userId()->equals($command->userId)) {
                return;
            }

            throw SocialAccountAlreadyLinkedException::create();
        }

        $this->links->save(LinkedAccount::link(
            id: $this->links->nextId(),
            userId: $command->userId,
            provider: $profile->provider,
            providerId: $profile->providerId,
            now: $this->clock->now(),
        ));
    }
}
