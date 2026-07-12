<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\RegenerateRecoveryCodes;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\RecoveryCodeGenerator;
use NetCode\Identity\Application\Ports\TokenHasher;
use NetCode\Identity\Application\TwoFactorPolicy;
use NetCode\Identity\Domain\Contracts\UserRepository;

final readonly class RegenerateRecoveryCodesHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private TokenHasher $tokenHasher,
        private RecoveryCodeGenerator $recoveryCodes,
    ) {}

    public function __invoke(
        RegenerateRecoveryCodes $command,
    ): RecoveryCodes {
        $user = $this->users->getById($command->userId);

        $plainCodes = $this->recoveryCodes->generate(TwoFactorPolicy::RECOVERY_CODE_COUNT);
        $hashedCodes = array_map(fn (string $code): string => $this->tokenHasher->hash($code), $plainCodes);

        $user->regenerateRecoveryCodes($hashedCodes);
        $this->users->save($user);

        return new RecoveryCodes(
            codes: $plainCodes,
        );
    }
}
