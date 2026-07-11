<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{name: string, email: string, password: string} */
    private function credentials(): array
    {
        return [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'password123',
        ];
    }

    #[Test]
    public function a_user_registers_logs_in_reads_me_and_logs_out(): void
    {
        $register = $this->postJson('/auth/register', $this->credentials());
        $register->assertCreated();
        $this->assertIsString($register->json('id'));

        $login = $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'password123',
        ]);
        $login->assertOk();
        $token = $login->json('token');
        $this->assertIsString($token);

        $this->withToken($token)->getJson('/auth/me')
            ->assertOk()
            ->assertJsonPath('name', 'Ada')
            ->assertJsonPath('email', 'ada@example.test')
            ->assertJsonPath('realmId', null);

        // Assert revocation via DB state — re-requesting with the dead token in the same test
        // hits the in-process guard cache (a fresh HTTP process would 401 correctly).
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->withToken($token)->postJson('/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function login_with_a_wrong_password_is_rejected(): void
    {
        $this->postJson('/auth/register', $this->credentials())->assertCreated();

        $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    #[Test]
    public function a_duplicate_email_is_rejected(): void
    {
        $this->postJson('/auth/register', $this->credentials())->assertCreated();

        $this->postJson('/auth/register', $this->credentials())->assertStatus(422);
    }

    #[Test]
    public function logging_out_everywhere_revokes_all_tokens(): void
    {
        $this->postJson('/auth/register', $this->credentials())->assertCreated();

        $first = $this->postJson('/auth/login', ['email' => 'ada@example.test', 'password' => 'password123'])->json('token');
        $this->postJson('/auth/login', ['email' => 'ada@example.test', 'password' => 'password123']);

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->withToken($first)->postJson('/auth/logout-all')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
