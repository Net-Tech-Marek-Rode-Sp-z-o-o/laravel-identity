<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\SocialLogin\SocialLogin;
use NetCode\Identity\Application\Commands\SocialLogin\SocialLoginHandler;
use NetCode\Identity\Application\Ports\SocialProfile;
use NetCode\Identity\Domain\Exceptions\SocialEmailNotVerifiedException;
use NetCode\Identity\Domain\LinkedAccount;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\LinkedAccountId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakeSocialIdentityProvider;
use NetCode\Identity\Tests\Support\FakeTokenIssuer;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\FixedRealmContext;
use NetCode\Identity\Tests\Support\InMemoryLinkedAccountRepository;
use NetCode\Identity\Tests\Support\InMemoryUserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SocialLoginHandlerTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryLinkedAccountRepository $links;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository;
        $this->links = new InMemoryLinkedAccountRepository;
    }

    private function handler(SocialProfile $profile): SocialLoginHandler
    {
        return new SocialLoginHandler(
            clock: new FixedClock,
            realm: new FixedRealmContext,
            tokens: new FakeTokenIssuer,
            users: $this->users,
            links: $this->links,
            social: new FakeSocialIdentityProvider($profile),
        );
    }

    private function profile(
        string $providerId = 'g-1',
        string $email = 'social@example.test',
        bool $verified = true,
    ): SocialProfile {
        return new SocialProfile(
            provider: SocialProvider::Google,
            providerId: $providerId,
            email: new Email($email),
            emailVerified: $verified,
            name: 'Social User',
        );
    }

    private function saveUser(UserId $id, string $email): void
    {
        $this->users->save(User::reconstitute(
            id: $id,
            realmId: null,
            email: new Email($email),
            name: 'Existing',
            passwordHash: 'hash',
            twoFactor: null,
            deletedAt: null,
        ));
    }

    #[Test]
    public function it_issues_a_token_for_an_already_linked_account(): void
    {
        $id = UserId::random();
        $this->saveUser($id, 'social@example.test');
        $this->links->save(LinkedAccount::link(
            id: LinkedAccountId::random(),
            userId: $id,
            provider: SocialProvider::Google,
            providerId: 'g-1',
            now: (new FixedClock)->now(),
        ));

        $token = ($this->handler($this->profile()))(new SocialLogin(SocialProvider::Google, 'tok'));

        $this->assertSame('token-'.$id->value(), $token);
        $this->assertSame(1, $this->links->count());
    }

    #[Test]
    public function it_links_a_verified_email_to_an_existing_user(): void
    {
        $id = UserId::random();
        $this->saveUser($id, 'social@example.test');

        $token = ($this->handler($this->profile(providerId: 'g-new')))(new SocialLogin(SocialProvider::Google, 'tok'));

        $this->assertSame('token-'.$id->value(), $token);
        $this->assertNotNull($this->links->findByProvider(SocialProvider::Google, 'g-new'));
    }

    #[Test]
    public function it_rejects_an_unverified_email_matching_an_existing_user(): void
    {
        $this->saveUser(UserId::random(), 'social@example.test');

        $this->expectException(SocialEmailNotVerifiedException::class);

        ($this->handler($this->profile(verified: false)))(new SocialLogin(SocialProvider::Google, 'tok'));
    }

    #[Test]
    public function it_creates_a_passwordless_user_when_none_exists(): void
    {
        $token = ($this->handler($this->profile(providerId: 'g-x', email: 'new@example.test')))(
            new SocialLogin(SocialProvider::Google, 'tok'),
        );

        $this->assertStringStartsWith('token-', $token);
        $user = $this->users->findByEmail(null, new Email('new@example.test'));
        $this->assertNotNull($user);
        $this->assertNull($user->passwordHash());
        $this->assertNotNull($this->links->findByProvider(SocialProvider::Google, 'g-x'));
    }
}
