<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\ResetPassword;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Port\PasswordHasher;
use NetCode\Identity\Domain\Contract\PasswordResetTokenRepository;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\Exception\InvalidResetTokenException;
use NetCode\Kit\Clock;

final readonly class ResetPasswordHandler implements CommandHandler
{
    public function __construct(
        private Clock $clock,
        private PasswordHasher $hasher,
        private UserRepository $users,
        private PasswordResetTokenRepository $tokens,
    ) {}

    public function __invoke(
        ResetPassword $command,
    ): null {
        $token = $this->tokens->findByHash(hash('sha256', $command->token));

        if ($token === null) {
            throw InvalidResetTokenException::notFound();
        }

        $token->redeem($this->clock->now());

        $user = $this->users->getById($token->userId());
        $user->changePassword($this->hasher->hash($command->password), $this->clock->now());

        $this->users->save($user);
        $this->tokens->save($token);

        return null;
    }
}
