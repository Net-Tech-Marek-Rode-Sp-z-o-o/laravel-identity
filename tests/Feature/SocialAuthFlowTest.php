<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Application\Ports\SocialIdentityProvider;
use NetCode\Identity\Application\Ports\SocialProfile;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Tests\Support\FakeSocialIdentityProvider;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class SocialAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private function bindSocial(
        string $providerId = 'g-1',
        string $email = 'bob@social.test',
        bool $verified = true,
    ): void {
        $this->app->instance(SocialIdentityProvider::class, new FakeSocialIdentityProvider(new SocialProfile(
            provider: SocialProvider::Google,
            providerId: $providerId,
            email: new Email($email),
            emailVerified: $verified,
            name: 'Bob Social',
        )));
    }

    private function registerAndLogin(string $email): string
    {
        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => $email,
            'password' => 'password123',
        ])->assertCreated();

        return (string) $this->postJson('/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('data.token');
    }

    #[Test]
    public function social_login_creates_a_user_and_returns_a_token(): void
    {
        $this->bindSocial();

        $login = $this->postJson('/auth/google/login', ['accessToken' => 'oauth-token']);
        $login->assertOk();
        $token = (string) $login->json('data.token');

        $this->withToken($token)->getJson('/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'bob@social.test');

        // A second social login with the same provider account reuses the user.
        $this->postJson('/auth/google/login', ['accessToken' => 'oauth-token'])->assertOk();
        $this->assertDatabaseCount('identity_users', 1);
        $this->assertDatabaseCount('identity_linked_accounts', 1);
    }

    #[Test]
    public function an_unknown_provider_is_not_found(): void
    {
        $this->bindSocial();

        $this->postJson('/auth/twitter/login', ['accessToken' => 'oauth-token'])->assertNotFound();
    }

    #[Test]
    public function an_authenticated_user_links_a_social_account(): void
    {
        $token = $this->registerAndLogin('ada@example.test');
        $this->bindSocial(providerId: 'g-777');

        $this->withToken($token)->postJson('/auth/google/link', ['accessToken' => 'oauth-token'])
            ->assertNoContent();

        $this->assertDatabaseCount('identity_linked_accounts', 1);
    }

    #[Test]
    public function linking_an_account_owned_by_another_user_conflicts(): void
    {
        // First user claims the social account via social login.
        $this->bindSocial(providerId: 'g-shared', email: 'alice@social.test');
        $this->postJson('/auth/google/login', ['accessToken' => 'oauth-token'])->assertOk();

        // A different, authenticated user tries to link the same provider account.
        $token = $this->registerAndLogin('bob@example.test');
        $this->bindSocial(providerId: 'g-shared', email: 'alice@social.test');

        $this->withToken($token)->postJson('/auth/google/link', ['accessToken' => 'oauth-token'])
            ->assertStatus(409);
    }

    #[Test]
    public function an_unverified_email_matching_an_existing_user_is_rejected(): void
    {
        $this->registerAndLogin('taken@example.test');
        $this->bindSocial(email: 'taken@example.test', verified: false);

        $this->postJson('/auth/google/login', ['accessToken' => 'oauth-token'])->assertStatus(422);
    }
}
