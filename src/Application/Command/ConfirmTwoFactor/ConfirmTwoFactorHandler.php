<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\ConfirmTwoFactor;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Port\SecretEncrypter;
use NetCode\Identity\Application\Port\Totp;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\Exception\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\Exception\TwoFactorNotEnrolledException;
use NetCode\Kit\Clock;

final readonly class ConfirmTwoFactorHandler implements CommandHandler
{
    public function __construct(
        private Totp $totp,
        private Clock $clock,
        private UserRepository $users,
        private SecretEncrypter $secrets,
    ) {}

    public function __invoke(
        ConfirmTwoFactor $command,
    ): null {
        $user = $this->users->getById($command->userId);

        $settings = $user->twoFactor();
        if ($settings === null) {
            throw TwoFactorNotEnrolledException::create();
        }

        if (! $this->totp->verify($this->secrets->decrypt($settings->secret), $command->code)) {
            throw InvalidTwoFactorCodeException::create();
        }

        $user->confirmTwoFactor($this->clock->now());
        $this->users->save($user);

        return null;
    }
}
