<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\LinkSocialAccount;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\UserId;

/** @implements Command<null> */
#[HandledBy(LinkSocialAccountHandler::class)]
final readonly class LinkSocialAccount implements Command
{
    public function __construct(
        public UserId $userId,
        public SocialProvider $provider,
        public string $accessToken,
    ) {}
}
