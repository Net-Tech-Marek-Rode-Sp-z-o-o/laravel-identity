<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\VerifyTwoFactorChallenge;

use NetCode\Bus\Command\CommandHandler;
use NetCode\Identity\Application\Ports\ChallengeTokenFactory;
use NetCode\Identity\Application\Ports\SecretEncrypter;
use NetCode\Identity\Application\Ports\TokenHasher;
use NetCode\Identity\Application\Ports\TokenIssuer;
use NetCode\Identity\Application\Ports\Totp;
use NetCode\Identity\Domain\Contracts\UserRepository;
use NetCode\Identity\Domain\Exceptions\InvalidChallengeTokenException;
use NetCode\Identity\Domain\Exceptions\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\Exceptions\TwoFactorNotEnrolledException;

final readonly class VerifyTwoFactorChallengeHandler implements CommandHandler
{
    public function __construct(
        private Totp $totp,
        private TokenIssuer $issuer,
        private TokenHasher $tokenHasher,
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
            $hashedCode = $this->tokenHasher->hash($command->code);

            if (! $user->hasRecoveryCode($hashedCode)) {
                throw InvalidTwoFactorCodeException::create();
            }

            $user->consumeRecoveryCode($hashedCode);
            $this->users->save($user);
        }

        return $this->issuer->issue($user->id());
    }
}
