<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Application\Ports\AccountDeletionHook;
use NetCode\Identity\Tests\Support\RecordingAccountDeletionHook;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class AccountDeletionFlowTest extends TestCase
{
    use RefreshDatabase;

    private RecordingAccountDeletionHook $hook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hook = new RecordingAccountDeletionHook;
        $this->app->instance(AccountDeletionHook::class, $this->hook);
    }

    private function register(): string
    {
        $id = $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->assertCreated()->json('data.id');
        $this->assertIsString($id);

        return $id;
    }

    private function login(): string
    {
        $token = $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->assertOk()->json('data.token');
        $this->assertIsString($token);

        return $token;
    }

    #[Test]
    public function a_user_deletes_the_account_and_can_no_longer_log_in(): void
    {
        $id = $this->register();
        $token = $this->login();

        $this->withToken($token)->deleteJson('/auth/me', ['password' => 'password123'])->assertNoContent();

        $this->assertSame($id, $this->hook->user?->id);
        $this->assertSame('ada@example.test', $this->hook->user?->email);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/auth/me')->assertUnauthorized();
        $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->assertUnauthorized();
    }

    #[Test]
    public function the_same_email_can_register_again_after_deletion(): void
    {
        $firstId = $this->register();
        $token = $this->login();
        $this->withToken($token)->deleteJson('/auth/me', ['password' => 'password123'])->assertNoContent();

        $secondId = $this->register();

        $this->assertNotSame($firstId, $secondId);
    }

    #[Test]
    public function a_wrong_password_is_rejected(): void
    {
        $this->register();
        $token = $this->login();

        $this->withToken($token)->deleteJson('/auth/me', ['password' => 'wrong-password'])->assertStatus(422);

        $this->assertNull($this->hook->user);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/auth/me')->assertOk();
    }

    #[Test]
    public function deletion_needs_authentication(): void
    {
        $this->deleteJson('/auth/me', ['password' => 'password123'])->assertUnauthorized();
    }
}
