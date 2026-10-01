<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use NetCode\Identity\Application\Ports\InvitationAcceptanceHook;
use NetCode\Identity\Application\Ports\InvitationAccess;
use NetCode\Identity\Application\Ports\InvitationMetadataFactory;
use NetCode\Identity\Application\Ports\InvitationNotifier;
use NetCode\Identity\Domain\ValueObjects\InvitationId;
use NetCode\Identity\Tests\Support\AllowEveryoneInvitationAccess;
use NetCode\Identity\Tests\Support\FixedInvitationMetadataFactory;
use NetCode\Identity\Tests\Support\RecordingInvitationAcceptanceHook;
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
        return $this->tokenOf('Ada', 'ada@example.test');
    }

    private function tokenOf(string $name, string $email): string
    {
        $this->postJson('/auth/register', [
            'name' => $name,
            'email' => $email,
            'password' => 'password123',
        ])->assertCreated();

        $token = (string) $this->postJson('/auth/login', [
            'email' => $email,
            'password' => 'password123',
        ])->json('data.token');
        $this->app['auth']->forgetGuards();

        return $token;
    }

    #[Test]
    public function an_invited_user_accepts_and_can_log_in(): void
    {
        $token = $this->inviterToken();
        $notifier = $this->spyNotifier();

        $invite = $this->withToken($token)->postJson('/auth/invitations', [
            'email' => 'bob@example.test',
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
    public function the_server_sets_the_invitation_metadata_not_the_client(): void
    {
        $this->app->instance(InvitationMetadataFactory::class, new FixedInvitationMetadataFactory(['household_id' => 'h-1']));
        $hook = new RecordingInvitationAcceptanceHook;
        $this->app->instance(InvitationAcceptanceHook::class, $hook);
        $token = $this->inviterToken();
        $notifier = $this->spyNotifier();

        $this->withToken($token)->postJson('/auth/invitations', [
            'email' => 'bob@example.test',
            'metadata' => ['household_id' => 'someone-elses'],
        ])->assertCreated();
        $this->postJson('/auth/invitations/accept', [
            'token' => (string) $notifier->token,
            'name' => 'Bob',
            'password' => 'bob-password-1',
        ])->assertCreated();

        $this->assertSame(['household_id' => 'h-1'], $hook->accepted?->metadata);
    }

    #[Test]
    public function another_user_cannot_revoke_an_invitation(): void
    {
        $token = $this->inviterToken();
        $notifier = $this->spyNotifier();
        $invitationId = (string) $this->withToken($token)
            ->postJson('/auth/invitations', ['email' => 'bob@example.test'])
            ->json('data.id');
        $otherToken = $this->tokenOf('Carl', 'carl@example.test');

        $this->withToken($otherToken)->deleteJson('/auth/invitations/'.$invitationId)->assertNotFound();

        $this->postJson('/auth/invitations/accept', [
            'token' => (string) $notifier->token,
            'name' => 'Bob',
            'password' => 'bob-password-1',
        ])->assertCreated();
    }

    #[Test]
    public function revoking_an_unknown_invitation_is_not_found(): void
    {
        $token = $this->inviterToken();

        $this->withToken($token)->deleteJson('/auth/invitations/'.InvitationId::random()->value())->assertNotFound();
        $this->withToken($token)->deleteJson('/auth/invitations/abc')->assertNotFound();
    }

    #[Test]
    public function an_invitation_without_an_inviter_cannot_be_accepted(): void
    {
        $hook = new RecordingInvitationAcceptanceHook;
        $this->app->instance(InvitationAcceptanceHook::class, $hook);
        $token = $this->inviterToken();
        $notifier = $this->spyNotifier();
        $this->withToken($token)->postJson('/auth/invitations', ['email' => 'bob@example.test'])->assertCreated();
        DB::table('identity_invitations')->update(['invited_by' => null]);

        $this->postJson('/auth/invitations/accept', [
            'token' => (string) $notifier->token,
            'name' => 'Bob',
            'password' => 'bob-password-1',
        ])->assertStatus(422);

        $this->assertNull($hook->accepted);
    }

    #[Test]
    public function the_host_can_let_another_user_revoke_an_invitation(): void
    {
        $this->app->instance(InvitationAccess::class, new AllowEveryoneInvitationAccess);
        $token = $this->inviterToken();
        $this->spyNotifier();
        $invitationId = (string) $this->withToken($token)
            ->postJson('/auth/invitations', ['email' => 'bob@example.test'])
            ->json('data.id');
        $otherToken = $this->tokenOf('Carl', 'carl@example.test');

        $this->withToken($otherToken)->deleteJson('/auth/invitations/'.$invitationId)->assertNoContent();
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
