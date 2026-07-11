<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Domain;

use DateTimeImmutable;
use NetCode\Identity\Domain\Event\UserDeleted;
use NetCode\Identity\Domain\Event\UserRegistered;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\UserId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    #[Test]
    public function it_registers_a_user_and_records_the_event(): void
    {
        $id = UserId::random();

        $user = User::register(
            id: $id,
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            now: new DateTimeImmutable,
        );

        $this->assertTrue($user->id()->equals($id));
        $this->assertNull($user->realmId());
        $this->assertSame('Ada', $user->name());
        $this->assertSame('ada@example.test', $user->email()->value());

        $events = $user->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserRegistered::class, $events[0]);
    }

    #[Test]
    public function it_records_a_deleted_event_and_marks_the_user_deleted(): void
    {
        $user = User::register(
            id: UserId::random(),
            realmId: null,
            email: new Email('ada@example.test'),
            name: 'Ada',
            passwordHash: 'hash',
            now: new DateTimeImmutable,
        );
        $user->releaseEvents();

        $user->delete(new DateTimeImmutable);

        $this->assertTrue($user->isDeleted());
        $events = $user->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserDeleted::class, $events[0]);
    }
}
