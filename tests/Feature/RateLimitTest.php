<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use NetCode\Identity\Application\Ports\ChallengeTokenFactory;
use NetCode\Identity\Application\Ports\InvitationNotifier;
use NetCode\Identity\Application\Ports\PasswordResetNotifier;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakeChallengeTokenFactory;
use NetCode\Identity\Tests\Support\SpyInvitationNotifier;
use NetCode\Identity\Tests\Support\SpyPasswordResetNotifier;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_limits_login_attempts_per_email_and_ip(): void
    {
        config()->set('identity.throttle.login_per_minute', 2);
        $credentials = ['email' => 'ada@example.test', 'password' => 'wrong-password'];

        $this->postJson('/auth/login', $credentials)->assertUnauthorized();
        $this->postJson('/auth/login', $credentials)->assertUnauthorized();

        $this->postJson('/auth/login', $credentials)->assertTooManyRequests();
    }

    #[Test]
    public function it_limits_logins_from_one_ip_across_emails(): void
    {
        config()->set('identity.throttle.login_per_minute_per_ip', 2);

        $this->postJson('/auth/login', ['email' => 'a@example.test', 'password' => 'wrong-password'])->assertUnauthorized();
        $this->postJson('/auth/login', ['email' => 'b@example.test', 'password' => 'wrong-password'])->assertUnauthorized();

        $this->postJson('/auth/login', ['email' => 'c@example.test', 'password' => 'wrong-password'])->assertTooManyRequests();
    }

    #[Test]
    public function it_counts_an_email_the_same_whatever_its_casing_and_spaces(): void
    {
        config()->set('identity.throttle.login_per_minute', 1);

        $this->postJson('/auth/login', ['email' => 'Ada@Example.test', 'password' => 'wrong-password'])->assertUnauthorized();

        $this->postJson('/auth/login', ['email' => ' ada@example.test ', 'password' => 'wrong-password'])->assertTooManyRequests();
    }

    #[Test]
    public function it_answers_a_malformed_email_without_a_server_error(): void
    {
        $this->postJson('/auth/login', ['email' => ['ada@example.test'], 'password' => 'x'])->assertUnprocessable();
    }

    #[Test]
    public function it_limits_password_reset_mails_per_email_across_ips(): void
    {
        config()->set('identity.throttle.password_requests_per_hour_per_email', 1);
        $this->app->instance(PasswordResetNotifier::class, new SpyPasswordResetNotifier);

        $first = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->postJson('/auth/password/forgot', ['email' => 'ada@example.test']);
        $this->assertNotSame(429, $first->status());

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->postJson('/auth/password/forgot', ['email' => 'ada@example.test'])
            ->assertTooManyRequests();
    }

    #[Test]
    public function it_limits_two_factor_attempts_per_user_across_ips(): void
    {
        config()->set('identity.throttle.two_factor_per_minute', 1);
        $this->app->instance(ChallengeTokenFactory::class, new FakeChallengeTokenFactory);
        $challenge = ['challengeToken' => 'challenge:'.UserId::random()->value(), 'code' => '000000'];

        $first = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->postJson('/auth/2fa/challenge', $challenge);
        $this->assertNotSame(429, $first->status());

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->postJson('/auth/2fa/challenge', $challenge)
            ->assertTooManyRequests();
    }

    #[Test]
    public function it_limits_token_guesses_per_ip(): void
    {
        config()->set('identity.throttle.tokens_per_minute', 1);

        $first = $this->postJson('/auth/email/verify', ['token' => 'guess-1']);
        $this->assertNotSame(429, $first->status());

        $this->postJson('/auth/email/verify', ['token' => 'guess-2'])->assertTooManyRequests();
    }

    #[Test]
    public function the_host_can_replace_a_limiter_by_its_name(): void
    {
        RateLimiter::for('identity-register', static fn (): Limit => Limit::perMinute(1)->by('host'));

        $this->postJson('/auth/register', ['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'password123'])->assertCreated();

        $this->postJson('/auth/register', ['name' => 'Bob', 'email' => 'bob@example.test', 'password' => 'password123'])->assertTooManyRequests();
    }

    #[Test]
    public function it_limits_registrations_per_ip(): void
    {
        config()->set('identity.throttle.register_per_minute', 1);

        $this->postJson('/auth/register', ['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'password123'])->assertCreated();

        $this->postJson('/auth/register', ['name' => 'Bob', 'email' => 'bob@example.test', 'password' => 'password123'])->assertTooManyRequests();
    }

    #[Test]
    public function an_unverified_user_cannot_send_invitations(): void
    {
        $token = $this->tokenOf('ada@example.test', verified: false);

        $this->withToken($token)->postJson('/auth/invitations', ['email' => 'bob@example.test'])->assertForbidden();
    }

    #[Test]
    public function it_limits_the_invitations_one_user_sends(): void
    {
        config()->set('identity.throttle.mail_per_hour', 1);
        $this->app->instance(InvitationNotifier::class, new SpyInvitationNotifier);
        $token = $this->tokenOf('ada@example.test', verified: true);

        $this->withToken($token)->postJson('/auth/invitations', ['email' => 'bob@example.test'])->assertCreated();

        $this->withToken($token)->postJson('/auth/invitations', ['email' => 'carl@example.test'])->assertTooManyRequests();
    }

    private function tokenOf(string $email, bool $verified): string
    {
        $this->postJson('/auth/register', ['name' => 'Ada', 'email' => $email, 'password' => 'password123'])->assertCreated();

        if ($verified) {
            DB::table('identity_users')->where('email', $email)->update(['email_verified_at' => now()]);
        }

        return (string) $this->postJson('/auth/login', ['email' => $email, 'password' => 'password123'])->json('data.token');
    }
}
