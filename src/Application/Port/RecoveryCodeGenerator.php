<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

interface RecoveryCodeGenerator
{
    /** @return list<string> */
    public function generate(int $count): array;
}
