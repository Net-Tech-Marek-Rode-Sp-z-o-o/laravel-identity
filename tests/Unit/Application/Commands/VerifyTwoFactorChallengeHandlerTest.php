<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\VerifyTwoFactorChallenge\VerifyTwoFactorChallenge;
use NetCode\Identity\Application\Commands\VerifyTwoFactorChallenge\VerifyTwoFactorChallengeHandler;
use NetCode\Identity\Domain\Exceptions\InvalidChallengeTokenException;
use NetCode\Identity\Domain\Exceptions\InvalidTwoFactorCodeException;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\Security\Sha256TokenHasher;
use NetCode\Identity\Tests\Support\FakeChallengeTokenFactory;
use NetCode\Identity\Tests\Support\FakeSecretEncrypter;
use NetCode\Identity\Tests\Support\FakeTokenIssuer;
use NetCode\Identity\Tests\Support\FakeTotp;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use NetCode\Identity\Tests\Support\TwoFactorUserMother;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class VerifyTwoFactorChallengeHandlerTest extends TestCase
{
    private function handler(InMemoryUserRepository $users): VerifyTwoFactorChallengeHandler
    {
        return new VerifyTwoFactorChallengeHandler(
            tokenHasher: new Sha256TokenHasher,
            totp: new FakeTotp,
            issuer: new FakeTokenIssuer,
            users: $users,
            secrets: new FakeSecretEncrypter,
            challenges: new FakeChallengeTokenFactory,
        );
    }

    #[Test]
    public function it_issues_a_token_for_a_valid_totp_code(): void
    {
        $id = UserId::random();
        $users = new InMemoryUserRepository;
        $users->save(TwoFactorUserMother::confirmed($id));

        $challenge = (new FakeChallengeTokenFactory)->issue($id);

        $token = ($this->handler($users))(new VerifyTwoFactorChallenge(
            challengeToken: $challenge,
            code: FakeTotp::VALID_CODE,
        ));

        $this->assertStringStartsWith('token-', $token);
    }

    #[Test]
    public function it_rejects_an_invalid_challenge_token(): void
    {
        $this->expectException(InvalidChallengeTokenException::class);

        ($this->handler(new InMemoryUserRepository))(new VerifyTwoFactorChallenge(
            challengeToken: 'garbage',
            code: FakeTotp::VALID_CODE,
        ));
    }

    #[Test]
    public function it_accepts_a_recovery_code_and_consumes_it(): void
    {
        $id = UserId::random();
        $users = new InMemoryUserRepository;
        $users->save(TwoFactorUserMother::confirmed($id, ['RECOVER-ME']));

        $challenge = (new FakeChallengeTokenFactory)->issue($id);

        $token = ($this->handler($users))(new VerifyTwoFactorChallenge(
            challengeToken: $challenge,
            code: 'RECOVER-ME',
        ));

        $this->assertStringStartsWith('token-', $token);
        $this->assertFalse($users->getById($id)->hasRecoveryCode(hash('sha256', 'RECOVER-ME')));
    }

    #[Test]
    public function it_rejects_a_wrong_totp_and_unknown_recovery_code(): void
    {
        $id = UserId::random();
        $users = new InMemoryUserRepository;
        $users->save(TwoFactorUserMother::confirmed($id, ['RECOVER-ME']));

        $challenge = (new FakeChallengeTokenFactory)->issue($id);

        $this->expectException(InvalidTwoFactorCodeException::class);

        ($this->handler($users))(new VerifyTwoFactorChallenge(
            challengeToken: $challenge,
            code: 'nope',
        ));
    }
}
