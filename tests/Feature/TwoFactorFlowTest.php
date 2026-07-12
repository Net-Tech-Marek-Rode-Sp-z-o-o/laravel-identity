<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorFlowTest extends TestCase
{
    use RefreshDatabase;

    private function registerAndLogin(): string
    {
        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->assertCreated();

        return (string) $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->json('token');
    }

    private function otp(string $secret): string
    {
        return (new Google2FA)->getCurrentOtp($secret);
    }

    /** @return array{token: string, secret: string, recoveryCodes: list<string>} */
    private function enableAndConfirm(): array
    {
        $token = $this->registerAndLogin();

        $enable = $this->withToken($token)->postJson('/auth/2fa/enable');
        $enable->assertOk();
        $secret = (string) $enable->json('secret');
        /** @var list<string> $recoveryCodes */
        $recoveryCodes = $enable->json('recoveryCodes');

        $this->withToken($token)->postJson('/auth/2fa/confirm', ['code' => $this->otp($secret)])
            ->assertNoContent();

        return ['token' => $token, 'secret' => $secret, 'recoveryCodes' => $recoveryCodes];
    }

    #[Test]
    public function a_user_enrols_confirms_then_logs_in_via_the_challenge(): void
    {
        ['secret' => $secret] = $this->enableAndConfirm();

        $login = $this->postJson('/auth/login', ['email' => 'ada@example.test', 'password' => 'password123']);
        $login->assertOk()->assertJsonPath('twoFactorRequired', true);
        $challengeToken = (string) $login->json('challengeToken');
        $this->assertNotSame('', $challengeToken);

        $challenge = $this->postJson('/auth/2fa/challenge', [
            'challengeToken' => $challengeToken,
            'code' => $this->otp($secret),
        ]);
        $challenge->assertOk();
        $token = (string) $challenge->json('token');

        $this->withToken($token)->getJson('/auth/me')->assertOk()->assertJsonPath('email', 'ada@example.test');
    }

    #[Test]
    public function a_recovery_code_satisfies_the_challenge(): void
    {
        ['recoveryCodes' => $recoveryCodes] = $this->enableAndConfirm();

        $challengeToken = (string) $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->json('challengeToken');

        $this->postJson('/auth/2fa/challenge', [
            'challengeToken' => $challengeToken,
            'code' => $recoveryCodes[0],
        ])->assertOk();

        // The same recovery code cannot be reused.
        $nextChallenge = (string) $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->json('challengeToken');

        $this->postJson('/auth/2fa/challenge', [
            'challengeToken' => $nextChallenge,
            'code' => $recoveryCodes[0],
        ])->assertStatus(422);
    }

    #[Test]
    public function an_invalid_confirmation_code_is_rejected(): void
    {
        $token = $this->registerAndLogin();
        $this->withToken($token)->postJson('/auth/2fa/enable')->assertOk();

        $this->withToken($token)->postJson('/auth/2fa/confirm', ['code' => '000000'])
            ->assertStatus(422);
    }

    #[Test]
    public function disabling_two_factor_restores_direct_login(): void
    {
        ['token' => $token, 'secret' => $secret] = $this->enableAndConfirm();

        $this->withToken($token)->postJson('/auth/2fa/disable', ['code' => $this->otp($secret)])
            ->assertNoContent();

        $this->postJson('/auth/login', ['email' => 'ada@example.test', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonMissingPath('twoFactorRequired')
            ->assertJsonPath('token', fn (mixed $token): bool => is_string($token));
    }

    #[Test]
    public function recovery_codes_can_be_regenerated(): void
    {
        ['token' => $token, 'recoveryCodes' => $old] = $this->enableAndConfirm();

        $regenerate = $this->withToken($token)->postJson('/auth/2fa/recovery-codes');
        $regenerate->assertOk();
        /** @var list<string> $new */
        $new = $regenerate->json('recoveryCodes');

        $this->assertNotSame($old, $new);
        $this->assertCount(8, $new);
    }
}
