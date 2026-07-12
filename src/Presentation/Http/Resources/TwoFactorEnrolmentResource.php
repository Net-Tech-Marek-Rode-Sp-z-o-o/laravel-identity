<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NetCode\Identity\Application\Commands\EnableTwoFactor\TwoFactorEnrolment;

final class TwoFactorEnrolmentResource extends JsonResource
{
    public function __construct(
        private readonly TwoFactorEnrolment $enrolment,
    ) {
        parent::__construct($enrolment);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'secret' => $this->enrolment->secret,
            'otpauth_uri' => $this->enrolment->otpAuthUri,
            'recovery_codes' => $this->enrolment->recoveryCodes,
        ];
    }
}
