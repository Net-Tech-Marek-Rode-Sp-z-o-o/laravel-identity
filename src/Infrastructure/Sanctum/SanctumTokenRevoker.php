<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Sanctum;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use NetCode\Identity\Application\Ports\TokenRevoker;
use NetCode\Identity\Infrastructure\DataAccess\Models\UserModel;
use RuntimeException;

final readonly class SanctumTokenRevoker implements TokenRevoker
{
    public function __construct(
        private AuthFactory $auth,
    ) {}

    public function revokeCurrent(): void
    {
        $this->user()->currentAccessToken()->delete();
    }

    public function revokeAll(): void
    {
        $this->user()->tokens()->delete();
    }

    private function user(): UserModel
    {
        $user = $this->auth->guard('sanctum')->user();

        if (! $user instanceof UserModel) {
            throw new RuntimeException('No authenticated user in the current request.');
        }

        return $user;
    }
}
