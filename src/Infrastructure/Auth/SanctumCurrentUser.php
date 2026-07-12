<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Auth;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use NetCode\Identity\Application\Ports\AuthenticatedUser;
use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Infrastructure\DataAccess\Models\UserModel;
use RuntimeException;

final readonly class SanctumCurrentUser implements CurrentUser
{
    public function __construct(
        private AuthFactory $auth,
    ) {}

    public function user(): AuthenticatedUser
    {
        return $this->toAuthenticatedUser($this->authenticated());
    }

    public function userOrNull(): AuthenticatedUser|null
    {
        $user = $this->auth->guard('sanctum')->user();

        return $user instanceof UserModel ? $this->toAuthenticatedUser($user) : null;
    }

    public function tokenId(): string
    {
        return (string) $this->authenticated()->currentAccessToken()->getKey();
    }

    private function toAuthenticatedUser(UserModel $user): AuthenticatedUser
    {
        return new AuthenticatedUser(
            id: $user->id->value(),
            name: $user->name,
            email: $user->email,
            realmId: $user->realm_id?->value(),
        );
    }

    private function authenticated(): UserModel
    {
        $user = $this->auth->guard('sanctum')->user();

        if (! $user instanceof UserModel) {
            throw new RuntimeException('No authenticated user in the current request.');
        }

        return $user;
    }
}
