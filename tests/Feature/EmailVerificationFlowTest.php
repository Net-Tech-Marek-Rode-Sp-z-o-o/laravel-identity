<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use NetCode\Identity\Application\Ports\EmailVerificationNotifier;
use NetCode\Identity\Tests\Support\SpyEmailVerificationNotifier;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private SpyEmailVerificationNotifier $notifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = new SpyEmailVerificationNotifier;
        $this->app->instance(EmailVerificationNotifier::class, $this->notifier);

        Route::middleware(['api', 'auth:sanctum', 'identity.verified'])
            ->get('/protected', fn (): JsonResponse => new JsonResponse(['ok' => true]));
    }

    private function registerAndLogin(): string
    {
        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->assertCreated();

        $token = $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->json('data.token');
        $this->assertIsString($token);

        return $token;
    }

    #[Test]
    public function a_user_verifies_the_email_from_the_registration_mail(): void
    {
        $token = $this->registerAndLogin();

        $this->withToken($token)->getJson('/auth/me')->assertOk()->assertJsonPath('data.email_verified', false);
        $this->withToken($token)->getJson('/protected')->assertForbidden();

        $verificationToken = $this->notifier->token;
        $this->assertIsString($verificationToken);
        $this->postJson('/auth/email/verify', ['token' => $verificationToken])->assertNoContent();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/auth/me')->assertOk()->assertJsonPath('data.email_verified', true);
        $this->withToken($token)->getJson('/protected')->assertOk();
    }

    #[Test]
    public function a_used_token_is_rejected(): void
    {
        $this->registerAndLogin();
        $verificationToken = $this->notifier->token;

        $this->postJson('/auth/email/verify', ['token' => $verificationToken])->assertNoContent();
        $this->postJson('/auth/email/verify', ['token' => $verificationToken])->assertStatus(422);
    }

    #[Test]
    public function an_unknown_token_is_rejected(): void
    {
        $this->postJson('/auth/email/verify', ['token' => 'nonexistent'])->assertStatus(422);
    }

    #[Test]
    public function an_unverified_user_asks_for_a_new_mail(): void
    {
        $token = $this->registerAndLogin();

        $this->withToken($token)->postJson('/auth/email/resend')->assertNoContent();

        $this->assertSame(2, $this->notifier->sent);
    }

    #[Test]
    public function resend_needs_authentication(): void
    {
        $this->postJson('/auth/email/resend')->assertUnauthorized();
    }
}
