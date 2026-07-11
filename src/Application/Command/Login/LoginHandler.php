<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\Login;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Port\PasswordHasher;
use NetCode\Identity\Application\Port\RealmContext;
use NetCode\Identity\Application\Port\TokenIssuer;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\Exception\InvalidCredentialsException;
use NetCode\Identity\Domain\ValueObjects\Email;

final readonly class LoginHandler implements CommandHandler
{
    public function __construct(
        private RealmContext $realm,
        private TokenIssuer $tokens,
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {}

    public function __invoke(
        Login $command,
    ): string {
        $user = $this->users->findByEmail($this->realm->current(), new Email($command->email));

        if ($user === null
            || $user->passwordHash() === null
            || ! $this->hasher->verify($command->password, $user->passwordHash())) {
            throw InvalidCredentialsException::create();
        }

        return $this->tokens->issue($user->id());
    }
}
