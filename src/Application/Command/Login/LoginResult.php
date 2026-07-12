<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\Login;

final readonly class LoginResult
{
    private function __construct(
        public string|null $token,
        public string|null $challengeToken,
    ) {}

    public static function authenticated(string $token): self
    {
        return new self(token: $token, challengeToken: null);
    }

    public static function twoFactorRequired(string $challengeToken): self
    {
        return new self(token: null, challengeToken: $challengeToken);
    }

    public function requiresTwoFactor(): bool
    {
        return $this->challengeToken !== null;
    }
}
