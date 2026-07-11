<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resource;

use NetCode\Identity\Application\Port\AuthenticatedUser;
use Spatie\LaravelData\Data;

final class MeResource extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string|null $realmId,
    ) {}

    public static function fromAuthenticated(AuthenticatedUser $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            realmId: $user->realmId,
        );
    }
}
