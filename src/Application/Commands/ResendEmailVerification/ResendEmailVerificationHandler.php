<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\ResendEmailVerification;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\EmailVerificationIssuer;
use NetCode\Identity\Domain\Contracts\UserRepository;

final readonly class ResendEmailVerificationHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private EmailVerificationIssuer $issuer,
    ) {}

    public function __invoke(
        ResendEmailVerification $command,
    ): void {
        $this->issuer->issueFor(
            user: $this->users->getById(id: $command->userId),
        );
    }
}
