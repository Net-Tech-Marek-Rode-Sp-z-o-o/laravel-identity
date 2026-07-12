<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use NetCode\Identity\Application\Ports\InvitationNotifier;
use NetCode\Identity\Tests\Support\SpyInvitationNotifier;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class InvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function spyNotifier(): SpyInvitationNotifier
    {
        $notifier = new SpyInvitationNotifier;
        $this->app->instance(InvitationNotifier::class, $notifier);

        return $notifier;
    }

    private function inviterToken(): string
    {
        $this->postJson('/auth/register', [
            'name' => 'Ada',
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->assertCreated();

        return (string) $this->postJson('/auth/login', [
            'email' => 'ada@example.test',
            'password' => 'password123',
        ])->json('data.token');
    }

    #[Test]
    public function an_invited_user_accepts_and_can_log_in(): void
    {
        $token = $this->inviterToken();
        $notifier = $this->spyNotifier();

        $invite = $this->withToken($token)->postJson('/auth/invitations', [
            'email' => 'bob@example.test',
            'metadata' => ['role' => 'manager'],
        ]);
        $invite->assertCreated()
            ->assertJsonPath('data.email', 'bob@example.test')
            ->assertJsonPath('data.id', fn (mixed $id): bool => is_string($id));
        $this->assertIsString($invite->json('data.expires_at'));

        $inviteToken = $notifier->token;
        $this->assertIsString($inviteToken);

        $this->postJson('/auth/invitations/accept', [
            'token' => $inviteToken,
            'name' => 'Bob',
            'password' => 'bob-password-1',
        ])->assertCreated()->assertJsonPath('data.id', fn (mixed $id): bool => is_string($id));

        $this->postJson('/auth/login', ['email' => 'bob@example.test', 'password' => 'bob-password-1'])
            ->assertOk()
            ->assertJsonPath('data.token', fn (mixed $t): bool => is_string($t));
    }

    #[Test]
    public function a_revoked_invitation_cannot_be_accepted(): void
    {
        $token = $this->inviterToken();
        $notifier = $this->spyNotifier();

        $invite = $this->withToken($token)->postJson('/auth/invitations', ['email' => 'bob@example.test']);
        $invite->assertCreated();
        $invitationId = (string) $invite->json('data.id');

        $this->withToken($token)->deleteJson('/auth/invitations/'.$invitationId)->assertNoContent();

        $this->postJson('/auth/invitations/accept', [
            'token' => (string) $notifier->token,
            'name' => 'Bob',
            'password' => 'bob-password-1',
        ])->assertStatus(422);
    }

    #[Test]
    public function inviting_an_existing_user_email_is_rejected(): void
    {
        $token = $this->inviterToken();
        $this->spyNotifier();

        $this->withToken($token)->postJson('/auth/invitations', ['email' => 'ada@example.test'])
            ->assertStatus(422);
    }

    #[Test]
    public function accepting_with_an_invalid_token_is_rejected(): void
    {
        $this->postJson('/auth/invitations/accept', [
            'token' => 'nonexistent-token',
            'name' => 'Bob',
            'password' => 'bob-password-1',
        ])->assertStatus(422);
    }

    #[Test]
    public function inviting_requires_authentication(): void
    {
        $this->postJson('/auth/invitations', ['email' => 'bob@example.test'])->assertUnauthorized();
    }
}
