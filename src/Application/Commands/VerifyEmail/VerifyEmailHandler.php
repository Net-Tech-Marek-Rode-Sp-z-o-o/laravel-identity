<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\VerifyEmail;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\TokenHasher;
use NetCode\Identity\Domain\Contracts\EmailVerificationTokenRepository;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\InvalidVerificationTokenException;
use NetCode\Kit\Clock;

final readonly class VerifyEmailHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private TokenHasher $tokenHasher,
        private UserRepository $users,
        private EmailVerificationTokenRepository $tokens,
    ) {}

    public function __invoke(
        VerifyEmail $command,
    ): void {
        $token = $this->tokens->findByHash(tokenHash: $this->tokenHasher->hash(token: $command->token));

        if ($token === null) {
            throw InvalidVerificationTokenException::notFound();
        }

        $token->redeem(now: $this->clock->now());

        $user = $this->users->getById(id: $token->userId());
        $user->verifyEmail(now: $this->clock->now());

        $this->users->save(user: $user);
        $this->tokens->save(token: $token);
    }
}
