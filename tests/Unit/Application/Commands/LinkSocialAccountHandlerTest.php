<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Application\Commands;

use NetCode\Identity\Application\Commands\LinkSocialAccount\LinkSocialAccount;
use NetCode\Identity\Application\Commands\LinkSocialAccount\LinkSocialAccountHandler;
use NetCode\Identity\Application\Ports\SocialProfile;
use NetCode\Identity\Domain\Exceptions\SocialAccountAlreadyLinkedException;
use NetCode\Identity\Domain\LinkedAccount;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\LinkedAccountId;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Tests\Support\FakeSocialIdentityProvider;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\Support\InMemoryLinkedAccountRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LinkSocialAccountHandlerTest extends TestCase
{
    private function handler(InMemoryLinkedAccountRepository $links): LinkSocialAccountHandler
    {
        return new LinkSocialAccountHandler(
            clock: new FixedClock,
            links: $links,
            social: new FakeSocialIdentityProvider(new SocialProfile(
                provider: SocialProvider::Google,
                providerId: 'g-1',
                email: new Email('social@example.test'),
                emailVerified: true,
                name: 'Social User',
            )),
        );
    }

    #[Test]
    public function it_links_a_new_provider_to_the_current_user(): void
    {
        $userId = UserId::random();
        $links = new InMemoryLinkedAccountRepository;

        ($this->handler($links))(new LinkSocialAccount($userId, SocialProvider::Google, 'tok'));

        $linked = $links->findByProvider(SocialProvider::Google, 'g-1');
        $this->assertNotNull($linked);
        $this->assertTrue($linked->userId()->equals($userId));
    }

    #[Test]
    public function it_rejects_a_provider_id_already_linked_to_another_user(): void
    {
        $links = new InMemoryLinkedAccountRepository;
        $links->save(LinkedAccount::link(
            id: LinkedAccountId::random(),
            userId: UserId::random(),
            provider: SocialProvider::Google,
            providerId: 'g-1',
            now: (new FixedClock)->now(),
        ));

        $this->expectException(SocialAccountAlreadyLinkedException::class);

        ($this->handler($links))(new LinkSocialAccount(UserId::random(), SocialProvider::Google, 'tok'));
    }

    #[Test]
    public function linking_the_same_account_again_is_idempotent(): void
    {
        $userId = UserId::random();
        $links = new InMemoryLinkedAccountRepository;
        $links->save(LinkedAccount::link(
            id: LinkedAccountId::random(),
            userId: $userId,
            provider: SocialProvider::Google,
            providerId: 'g-1',
            now: (new FixedClock)->now(),
        ));

        ($this->handler($links))(new LinkSocialAccount($userId, SocialProvider::Google, 'tok'));

        $this->assertSame(1, $links->count());
    }
}
