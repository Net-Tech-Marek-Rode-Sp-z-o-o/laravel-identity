<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Commands\EnableTwoFactor;

final readonly class TwoFactorEnrolment
{
    /** @param list<string> $recoveryCodes */
    public function __construct(
        public string $secret,
        public string $otpAuthUri,
        public array $recoveryCodes,
    ) {}
}
