<?php

declare(strict_types=1);

namespace NetCode\Identity\Application;

use DateInterval;
use NetCode\Identity\Application\Ports\EmailVerificationNotifier;
use NetCode\Identity\Application\Ports\TokenGenerator;
use NetCode\Identity\Application\Ports\TokenHasher;
use NetCode\Identity\Domain\Contracts\EmailVerificationTokenRepository;
use NetCode\Identity\Domain\EmailVerificationToken;
use NetCode\Identity\Domain\User;
use NetCode\Kit\Clock;

final readonly class EmailVerificationIssuer
{
    public function __construct(
        private Clock $clock,
        private TokenGenerator $generator,
        private TokenHasher $tokenHasher,
        private EmailVerificationNotifier $notifier,
        private EmailVerificationTokenRepository $tokens,
        private int $ttlMinutes,
    ) {}

    public function issueFor(User $user): void
    {
        if ($user->isEmailVerified()) {
            return;
        }

        $token = $this->generator->generate();

        $verification = EmailVerificationToken::issue(
            id: $this->tokens->nextId(),
            userId: $user->id(),
            tokenHash: $this->tokenHasher->hash(token: $token),
            expiresAt: $this->clock->now()->add(new DateInterval(duration: 'PT'.$this->ttlMinutes.'M')),
        );

        $this->tokens->save(token: $verification);
        $this->notifier->notify(
            email: $user->email(),
            token: $token,
            expiresAt: $verification->expiresAt(),
        );
    }
}
