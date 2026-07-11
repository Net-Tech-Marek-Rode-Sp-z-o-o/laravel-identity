<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Unit\Domain;

use NetCode\Domain\Exception\InvalidArgumentException;
use NetCode\Identity\Domain\ValueObjects\Email;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    #[Test]
    public function it_trims_and_lowercases(): void
    {
        $this->assertSame('ada@example.test', new Email('  ADA@Example.test ')->value());
    }

    #[Test]
    public function it_rejects_an_invalid_address(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('not-an-email');
    }
}
