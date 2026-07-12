<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Application\Ports\PasswordResetNotifier;
use NetCode\Identity\Tests\Support\SpyPasswordResetNotifier;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    private function spyNotifier(): SpyPasswordResetNotifier
    {
        $notifier = new SpyPasswordResetNotifier;
        $this->app->instance(PasswordResetNotifier::class, $notifier);

        return $notifier;
    }

    private function registerAda(): void
    {
        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->assertCreated();
    }

    #[Test]
    public function a_user_resets_a_forgotten_password(): void
    {
        $this->registerAda();
        $notifier = $this->spyNotifier();

        $this->postJson('/auth/password/forgot', ['email' => 'ada@example.test'])->assertNoContent();
        $token = $notifier->token;
        $this->assertIsString($token);

        $this->postJson('/auth/password/reset', [
            'token' => $token,
            'password' => 'new-password-123',
        ])->assertNoContent();

        $this->postJson('/auth/login', ['email' => 'ada@example.test', 'password' => 'new-password-123'])->assertOk();
        $this->postJson('/auth/login', ['email' => 'ada@example.test', 'password' => 'password123'])->assertStatus(401);
    }

    #[Test]
    public function forgot_for_an_unknown_email_is_silent(): void
    {
        $notifier = $this->spyNotifier();

        $this->postJson('/auth/password/forgot', ['email' => 'nobody@example.test'])->assertNoContent();

        $this->assertNull($notifier->token);
    }

    #[Test]
    public function reset_with_an_invalid_token_is_rejected(): void
    {
        $this->postJson('/auth/password/reset', [
            'token' => 'nonexistent-token',
            'password' => 'new-password-123',
        ])->assertStatus(422);
    }
}
