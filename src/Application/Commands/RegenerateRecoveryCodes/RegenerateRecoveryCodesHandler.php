<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\RegenerateRecoveryCodes;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\RecoveryCodeGenerator;
use NetCode\Identity\Application\TwoFactorPolicy;
use NetCode\Identity\Domain\Contracts\UserRepository;

final readonly class RegenerateRecoveryCodesHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private RecoveryCodeGenerator $recoveryCodes,
    ) {}

    public function __invoke(
        RegenerateRecoveryCodes $command,
    ): RecoveryCodes {
        $user = $this->users->getById($command->userId);

        $plainCodes = $this->recoveryCodes->generate(TwoFactorPolicy::RECOVERY_CODE_COUNT);
        $hashedCodes = array_map(static fn (string $code): string => hash('sha256', $code), $plainCodes);

        $user->regenerateRecoveryCodes($hashedCodes);
        $this->users->save($user);

        return new RecoveryCodes(
            codes: $plainCodes,
        );
    }
}
