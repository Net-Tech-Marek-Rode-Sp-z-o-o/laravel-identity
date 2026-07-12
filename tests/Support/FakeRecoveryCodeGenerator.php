<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Port\RecoveryCodeGenerator;

final class FakeRecoveryCodeGenerator implements RecoveryCodeGenerator
{
    /** @return list<string> */
    public function generate(int $count): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = 'RECOVERY-'.$i;
        }

        return $codes;
    }
}
