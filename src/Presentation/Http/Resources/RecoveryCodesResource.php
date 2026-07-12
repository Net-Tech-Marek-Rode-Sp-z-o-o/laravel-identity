<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NetCode\Identity\Application\Commands\RegenerateRecoveryCodes\RecoveryCodes;

final class RecoveryCodesResource extends JsonResource
{
    public function __construct(
        private readonly RecoveryCodes $codes,
    ) {
        parent::__construct($codes);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'recovery_codes' => $this->codes->codes,
        ];
    }
}
