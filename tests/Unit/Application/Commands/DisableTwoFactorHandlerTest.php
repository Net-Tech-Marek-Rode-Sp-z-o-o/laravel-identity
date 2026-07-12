<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\DisableTwoFactor\DisableTwoFactor;
use NetCode\Identity\Application\Commands\DisableTwoFactor\DisableTwoFactorHandler;
use NetCode\Identity\Domain\Exceptions\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakeSecretEncrypter;
use NetCode\Identity\Tests\Support\FakeTotp;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\TwoFactorUserMother;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DisableTwoFactorHandlerTest extends TestCase
{
    private function handler(InMemoryUserRepository $users): DisableTwoFactorHandler
    {
        return new DisableTwoFactorHandler(
            totp: new FakeTotp,
            clock: new FixedClock,
            users: $users,
            secrets: new FakeSecretEncrypter,
        );
    }

    #[Test]
    public function it_disables_two_factor_with_a_valid_code(): void
    {
        $id = UserId::random();
        $users = new InMemoryUserRepository;
        $users->save(TwoFactorUserMother::confirmed($id));

        ($this->handler($users))(new DisableTwoFactor(userId: $id, code: FakeTotp::VALID_CODE));

        $this->assertFalse($users->getById($id)->hasActiveTwoFactor());
        $this->assertNull($users->getById($id)->twoFactor());
    }

    #[Test]
    public function it_rejects_an_invalid_code(): void
    {
        $id = UserId::random();
        $users = new InMemoryUserRepository;
        $users->save(TwoFactorUserMother::confirmed($id));

        $this->expectException(InvalidTwoFactorCodeException::class);

        ($this->handler($users))(new DisableTwoFactor(userId: $id, code: '000000'));
    }
}
