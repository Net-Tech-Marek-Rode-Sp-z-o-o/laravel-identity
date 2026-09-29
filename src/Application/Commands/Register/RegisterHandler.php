<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\Register;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\EmailVerificationIssuer;
use NetCode\Identity\Application\Ports\PasswordHasher;
use NetCode\Identity\Application\Ports\PostRegistrationHook;
use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Application\Ports\RegisteredUser;
use NetCode\Identity\Application\Ports\RegistrationPayload;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Kit\Clock;

final readonly class RegisterHandler implements CommandHandler
{
    /** @param PostRegistrationHook<RegistrationPayload> $hook */
    public function __construct(
        private Clock $clock,
        private RealmContext $realm,
        private UserRepository $users,
        private PasswordHasher $hasher,
        private PostRegistrationHook $hook,
        private EmailVerificationIssuer $verification,
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

        $registeredUser = new RegisteredUser(
            id: $user->id()->value(),
            name: $user->name(),
            email: $user->email()->value(),
        );

        $this->hook->afterRegistration(
            user: $registeredUser,
            payload: $command->payload,
        );

        $this->verification->issueFor(user: $user);

        return $user->id()->value();
    }
}
