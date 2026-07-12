<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Security;

use Illuminate\Support\Str;
use NetCode\Identity\Application\Ports\RecoveryCodeGenerator;

final class RandomRecoveryCodeGenerator implements RecoveryCodeGenerator
{
    private const int LENGTH = 10;

    /** @return list<string> */
    public function generate(int $count): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::upper(Str::random(self::LENGTH));
        }

        return $codes;
    }
}
