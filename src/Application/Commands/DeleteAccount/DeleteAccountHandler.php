<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\DeleteAccount;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\AccountDeletionHook;
use NetCode\Identity\Application\Ports\DeletedUser;
use NetCode\Identity\Application\Ports\PasswordHasher;
use NetCode\Identity\Application\Ports\TokenRevoker;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\PasswordConfirmationFailedException;
use NetCode\Kit\Clock;

final readonly class DeleteAccountHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private UserRepository $users,
        private PasswordHasher $hasher,
        private TokenRevoker $tokens,
        private AccountDeletionHook $hook,
    ) {}

    public function __invoke(
        DeleteAccount $command,
    ): void {
        $user = $this->users->getById(id: $command->userId);

        $hash = $user->passwordHash();
        if ($hash !== null && ($command->password === null || ! $this->hasher->verify(plain: $command->password, hash: $hash))) {
            throw PasswordConfirmationFailedException::create();
        }

        $user->delete(now: $this->clock->now());
        $this->users->save(user: $user);

        $this->tokens->revokeAll();

        $this->hook->afterDeletion(user: new DeletedUser(
            id: $user->id()->value(),
            name: $user->name(),
            email: $user->email()->value(),
            realmId: $user->realmId()?->value(),
        ));
    }
}
