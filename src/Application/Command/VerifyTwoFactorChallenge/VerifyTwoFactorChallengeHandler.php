<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\VerifyTwoFactorChallenge;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Port\ChallengeTokenFactory;
use NetCode\Identity\Application\Port\SecretEncrypter;
use NetCode\Identity\Application\Port\TokenIssuer;
use NetCode\Identity\Application\Port\Totp;
use NetCode\Identity\Domain\Contract\UserRepository;
use NetCode\Identity\Domain\Exception\InvalidChallengeTokenException;
use NetCode\Identity\Domain\Exception\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\Exception\TwoFactorNotEnrolledException;

final readonly class VerifyTwoFactorChallengeHandler implements CommandHandler
{
    public function __construct(
        private Totp $totp,
        private TokenIssuer $issuer,
        private UserRepository $users,
        private SecretEncrypter $secrets,
        private ChallengeTokenFactory $challenges,
    ) {}

    public function __invoke(
        VerifyTwoFactorChallenge $command,
    ): string {
        $userId = $this->challenges->verify($command->challengeToken);
        if ($userId === null) {
            throw InvalidChallengeTokenException::create();
        }

        $user = $this->users->getById($userId);

        $settings = $user->twoFactor();
        if ($settings === null) {
            throw TwoFactorNotEnrolledException::create();
        }

        if (! $this->totp->verify($this->secrets->decrypt($settings->secret), $command->code)) {
            $hashedCode = hash('sha256', $command->code);

            if (! $user->hasRecoveryCode($hashedCode)) {
                throw InvalidTwoFactorCodeException::create();
            }

            $user->consumeRecoveryCode($hashedCode);
            $this->users->save($user);
        }

        return $this->issuer->issue($user->id());
    }
}
