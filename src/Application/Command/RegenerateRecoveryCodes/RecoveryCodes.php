<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\RegenerateRecoveryCodes;

final readonly class RecoveryCodes
{
    /** @param list<string> $codes */
    public function __construct(
        public array $codes,
    ) {}
}
