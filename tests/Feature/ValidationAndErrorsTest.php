<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ValidationAndErrorsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function register_rejects_an_invalid_email(): void
    {
        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'not-an-email',
            'password' => 'password123',
        ])->assertStatus(422);
    }

    #[Test]
    public function register_rejects_a_short_password(): void
    {
        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'short',
        ])->assertStatus(422);
    }

    #[Test]
    public function me_requires_authentication(): void
    {
        $this->getJson('/auth/me')->assertUnauthorized();
    }

    #[Test]
    public function login_with_an_unknown_email_is_rejected(): void
    {
        $this->postJson('/auth/login', [
            'email' => 'nobody@example.test',
            'password' => 'password123',
        ])->assertStatus(401);
    }
}
