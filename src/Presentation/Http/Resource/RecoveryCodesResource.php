<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resource;

use NetCode\Identity\Application\Command\RegenerateRecoveryCodes\RecoveryCodes;
use Spatie\LaravelData\Data;

final class RecoveryCodesResource extends Data
{
    /** @param list<string> $recoveryCodes */
    public function __construct(
        public array $recoveryCodes,
    ) {}

    public static function fromRecoveryCodes(RecoveryCodes $codes): self
    {
        return new self(
            recoveryCodes: $codes->codes,
        );
    }
}
