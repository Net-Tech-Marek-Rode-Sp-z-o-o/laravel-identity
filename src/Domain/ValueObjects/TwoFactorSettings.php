<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\ValueObjects;

use DateTimeImmutable;

final readonly class TwoFactorSettings
{
    /** @param list<string> $recoveryCodes */
    public function __construct(
        public string $secret,
        public array $recoveryCodes,
        public DateTimeImmutable|null $confirmedAt,
    ) {}

    /** @param list<string> $recoveryCodes */
    public static function pending(
        string $secret,
        array $recoveryCodes,
    ): self {
        return new self(secret: $secret, recoveryCodes: $recoveryCodes, confirmedAt: null);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmedAt !== null;
    }

    public function confirm(DateTimeImmutable $at): self
    {
        return new self(secret: $this->secret, recoveryCodes: $this->recoveryCodes, confirmedAt: $at);
    }

    /** @param list<string> $recoveryCodes */
    public function withRecoveryCodes(array $recoveryCodes): self
    {
        return new self(secret: $this->secret, recoveryCodes: $recoveryCodes, confirmedAt: $this->confirmedAt);
    }

    public function hasRecoveryCode(string $code): bool
    {
        return in_array($code, $this->recoveryCodes, strict: true);
    }

    public function withoutRecoveryCode(string $code): self
    {
        $remaining = array_values(array_filter($this->recoveryCodes, static fn (string $c): bool => $c !== $code));

        return new self(secret: $this->secret, recoveryCodes: $remaining, confirmedAt: $this->confirmedAt);
    }
}
