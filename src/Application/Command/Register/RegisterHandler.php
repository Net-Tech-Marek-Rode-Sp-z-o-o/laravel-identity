<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\Register;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Port\PasswordHasher;
use NetCode\Identity\Application\Port\RealmContext;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\Exception\EmailAlreadyTakenException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Kit\Clock;

final readonly class RegisterHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private RealmContext $realm,
        private UserRepository $users,
        private PasswordHasher $hasher,
    ) {}

    public function __invoke(
        Register $command,
    ): string {
        $realm = $this->realm->current();
        $email = new Email($command->email);

        if ($this->users->findByEmail($realm, $email) !== null) {
            throw EmailAlreadyTakenException::for($email);
        }

        $user = User::register(
            id: $this->users->nextId(),
            realmId: $realm,
            email: $email,
            name: $command->name,
            passwordHash: $this->hasher->hash($command->password),
            now: $this->clock->now(),
        );

        $this->users->save($user);

        return $user->id()->value();
    }
}
