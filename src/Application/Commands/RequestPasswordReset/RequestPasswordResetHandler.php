<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\RequestPasswordReset;

use DateInterval;
use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\PasswordResetNotifier;
use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Application\Ports\TokenGenerator;
use NetCode\Identity\Application\Ports\TokenHasher;
use NetCode\Identity\Domain\Contracts\PasswordResetTokenRepository;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\PasswordResetToken;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Kit\Clock;

final readonly class RequestPasswordResetHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RealmContext $realm,
        private TokenGenerator $generator,
        private TokenHasher $tokenHasher,
        private UserRepository $users,
        private PasswordResetNotifier $notifier,
        private PasswordResetTokenRepository $tokens,
        private int $ttlMinutes,
    ) {}

    public function __invoke(
        RequestPasswordReset $command,
    ): void {
        $user = $this->users->findByEmail($this->realm->current(), new Email($command->email));

        if ($user === null) {
            return;
        }

        $token = $this->generator->generate();

        $reset = PasswordResetToken::issue(
            id: $this->tokens->nextId(),
            userId: $user->id(),
            tokenHash: $this->tokenHasher->hash($token),
            expiresAt: $this->clock->now()->add(new DateInterval('PT'.$this->ttlMinutes.'M')),
        );

        $this->tokens->save($reset);
        $this->notifier->notify($user->email(), $token, $reset->expiresAt());
    }
}
