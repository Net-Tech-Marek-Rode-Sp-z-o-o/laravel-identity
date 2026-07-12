<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Sanctum;

use NetCode\Identity\Application\Ports\TokenIssuer;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Infrastructure\DataAccess\Models\UserModel;

final readonly class SanctumTokenIssuer implements TokenIssuer
{
    public function __construct(
        private string $tokenName,
    ) {}

    public function issue(UserId $userId): string
    {
        $user = UserModel::query()->findOrFail($userId->value());

        return $user->createToken($this->tokenName)->plainTextToken;
    }
}
