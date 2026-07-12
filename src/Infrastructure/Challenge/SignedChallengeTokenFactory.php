<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Challenge;

use DateInterval;
use Illuminate\Contracts\Encryption\Encrypter;
use NetCode\Identity\Application\Port\ChallengeTokenFactory;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Kit\Clock;
use Throwable;

final readonly class SignedChallengeTokenFactory implements ChallengeTokenFactory
{
    public function __construct(
        private Clock $clock,
        private Encrypter $encrypter,
        private int $ttlMinutes,
    ) {}

    public function issue(UserId $userId): string
    {
        $expiresAt = $this->clock->now()->add(new DateInterval('PT'.$this->ttlMinutes.'M'));

        return $this->encrypter->encrypt([
            'sub' => $userId->value(),
            'exp' => $expiresAt->getTimestamp(),
        ]);
    }

    public function verify(string $challengeToken): UserId|null
    {
        try {
            $payload = $this->encrypter->decrypt($challengeToken);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['sub'], $payload['exp'])) {
            return null;
        }

        if ((int) $payload['exp'] < $this->clock->now()->getTimestamp()) {
            return null;
        }

        return UserId::fromString((string) $payload['sub']);
    }
}
