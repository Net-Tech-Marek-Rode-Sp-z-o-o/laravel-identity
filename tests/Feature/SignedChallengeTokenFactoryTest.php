<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Feature;

use DateTimeImmutable;
use Illuminate\Contracts\Encryption\Encrypter;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\Challenge\SignedChallengeTokenFactory;
use NetCode\Identity\Tests\Support\FixedClock;
use NetCode\Identity\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class SignedChallengeTokenFactoryTest extends TestCase
{
    private function factory(string $now): SignedChallengeTokenFactory
    {
        return new SignedChallengeTokenFactory(
            clock: new FixedClock(new DateTimeImmutable($now)),
            encrypter: $this->app->make(Encrypter::class),
            ttlMinutes: 5,
        );
    }

    #[Test]
    public function it_round_trips_a_user_id_within_the_ttl(): void
    {
        $id = UserId::random();

        $token = $this->factory('2026-01-01T00:00:00+00:00')->issue($id);

        $this->assertTrue($this->factory('2026-01-01T00:04:00+00:00')->verify($token)?->equals($id));
    }

    #[Test]
    public function it_rejects_an_expired_token(): void
    {
        $token = $this->factory('2026-01-01T00:00:00+00:00')->issue(UserId::random());

        $this->assertNull($this->factory('2026-01-01T00:06:00+00:00')->verify($token));
    }

    #[Test]
    public function it_rejects_a_malformed_token(): void
    {
        $this->assertNull($this->factory('2026-01-01T00:00:00+00:00')->verify('not-a-real-token'));
    }
}
