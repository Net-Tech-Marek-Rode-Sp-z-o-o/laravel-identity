<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resource;

use NetCode\Identity\Application\Command\EnableTwoFactor\TwoFactorEnrolment;
use Spatie\LaravelData\Data;

final class TwoFactorEnrolmentResource extends Data
{
    /** @param list<string> $recoveryCodes */
    public function __construct(
        public string $secret,
        public string $otpAuthUri,
        public array $recoveryCodes,
    ) {}

    public static function fromEnrolment(TwoFactorEnrolment $enrolment): self
    {
        return new self(
            secret: $enrolment->secret,
            otpAuthUri: $enrolment->otpAuthUri,
            recoveryCodes: $enrolment->recoveryCodes,
        );
    }
}
