<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\EnableTwoFactor;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Port\RecoveryCodeGenerator;
use NetCode\Identity\Application\Port\SecretEncrypter;
use NetCode\Identity\Application\Port\Totp;
use NetCode\Identity\Application\TwoFactorPolicy;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\ValueObjects\TwoFactorSettings;

final readonly class EnableTwoFactorHandler implements CommandHandler
{
    public function __construct(
        private Totp $totp,
        private string $issuer,
        private UserRepository $users,
        private SecretEncrypter $secrets,
        private RecoveryCodeGenerator $recoveryCodes,
    ) {}

    public function __invoke(
        EnableTwoFactor $command,
    ): TwoFactorEnrolment {
        $user = $this->users->getById($command->userId);

        $secret = $this->totp->generateSecret();
        $plainCodes = $this->recoveryCodes->generate(TwoFactorPolicy::RECOVERY_CODE_COUNT);
        $hashedCodes = array_map(static fn (string $code): string => hash('sha256', $code), $plainCodes);

        $user->enableTwoFactor(TwoFactorSettings::pending(
            secret: $this->secrets->encrypt($secret),
            recoveryCodes: $hashedCodes,
        ));
        $this->users->save($user);

        return new TwoFactorEnrolment(
            secret: $secret,
            otpAuthUri: $this->totp->provisioningUri($secret, $user->email()->value(), $this->issuer),
            recoveryCodes: $plainCodes,
        );
    }
}
