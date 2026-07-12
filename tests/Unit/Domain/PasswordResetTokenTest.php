<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Domain;

use DateTimeImmutable;
use NetCode\Identity\Domain\Exceptions\InvalidResetTokenException;
use NetCode\Identity\Domain\PasswordResetToken;
use NetCode\Identity\Domain\ValueObjects\PasswordResetTokenId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PasswordResetTokenTest extends TestCase
{
    private function token(DateTimeImmutable $expiresAt): PasswordResetToken
    {
        return PasswordResetToken::issue(
            id: PasswordResetTokenId::random(),
            userId: UserId::random(),
            tokenHash: 'hash',
            expiresAt: $expiresAt,
        );
    }

    #[Test]
    public function it_redeems_a_valid_token(): void
    {
        $token = $this->token(new DateTimeImmutable('2026-01-01T01:00:00+00:00'));

        $token->redeem(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));

        $this->assertNotNull($token->usedAt());
    }

    #[Test]
    public function it_rejects_an_expired_token(): void
    {
        $token = $this->token(new DateTimeImmutable('2025-12-31T23:59:00+00:00'));

        $this->expectException(InvalidResetTokenException::class);

        $token->redeem(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));
    }

    #[Test]
    public function it_rejects_an_already_used_token(): void
    {
        $token = $this->token(new DateTimeImmutable('2026-01-01T01:00:00+00:00'));
        $token->redeem(new DateTimeImmutable('2026-01-01T00:00:00+00:00'));

        $this->expectException(InvalidResetTokenException::class);

        $token->redeem(new DateTimeImmutable('2026-01-01T00:30:00+00:00'));
    }
}
