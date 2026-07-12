<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\RegenerateRecoveryCodes\RegenerateRecoveryCodes;
use NetCode\Identity\Application\Commands\RegenerateRecoveryCodes\RegenerateRecoveryCodesHandler;
use NetCode\Identity\Application\TwoFactorPolicy;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakeRecoveryCodeGenerator;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\TwoFactorUserMother;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegenerateRecoveryCodesHandlerTest extends TestCase
{
    #[Test]
    public function it_replaces_the_recovery_codes_and_returns_the_new_plain_codes(): void
    {
        $id = UserId::random();
        $users = new InMemoryUserRepository;
        $users->save(TwoFactorUserMother::confirmed($id, ['OLD-CODE']));

        $handler = new RegenerateRecoveryCodesHandler(
            users: $users,
            recoveryCodes: new FakeRecoveryCodeGenerator,
        );

        $result = $handler(new RegenerateRecoveryCodes(userId: $id));

        $this->assertCount(TwoFactorPolicy::RECOVERY_CODE_COUNT, $result->codes);

        $user = $users->getById($id);
        $this->assertFalse($user->hasRecoveryCode(hash('sha256', 'OLD-CODE')));
        $this->assertTrue($user->hasRecoveryCode(hash('sha256', $result->codes[0])));
    }
}
