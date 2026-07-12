<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\Login;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\ChallengeTokenFactory;
use NetCode\Identity\Application\Ports\PasswordHasher;
use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Application\Ports\TokenIssuer;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\InvalidCredentialsException;
use NetCode\Identity\Domain\ValueObjects\Email;

final readonly class LoginHandler implements CommandHandler
{
    public function __construct(
        private RealmContext $realm,
        private TokenIssuer $tokens,
        private UserRepository $users,
        private PasswordHasher $hasher,
        private ChallengeTokenFactory $challenges,
    ) {}

    public function __invoke(
        Login $command,
    ): LoginResult {
        $user = $this->users->findByEmail($this->realm->current(), new Email($command->email));

        if ($user === null
            || $user->passwordHash() === null
            || ! $this->hasher->verify($command->password, $user->passwordHash())) {
            throw InvalidCredentialsException::create();
        }

        if ($user->hasActiveTwoFactor()) {
            return LoginResult::twoFactorRequired($this->challenges->issue($user->id()));
        }

        return LoginResult::authenticated($this->tokens->issue($user->id()));
    }
}
