<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\DisableTwoFactor;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\SecretEncrypter;
use NetCode\Identity\Application\Ports\Totp;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\Exceptions\TwoFactorNotEnrolledException;
use NetCode\Kit\Clock;

final readonly class DisableTwoFactorHandler implements CommandHandler
{
    public function __construct(
        private Totp $totp,
        private Clock $clock,
        private UserRepository $users,
        private SecretEncrypter $secrets,
    ) {}

    public function __invoke(
        DisableTwoFactor $command,
    ): null {
        $user = $this->users->getById($command->userId);

        $settings = $user->twoFactor();
        if ($settings === null) {
            throw TwoFactorNotEnrolledException::create();
        }

        if (! $this->totp->verify($this->secrets->decrypt($settings->secret), $command->code)) {
            throw InvalidTwoFactorCodeException::create();
        }

        $user->disableTwoFactor($this->clock->now());
        $this->users->save($user);

        return null;
    }
}
