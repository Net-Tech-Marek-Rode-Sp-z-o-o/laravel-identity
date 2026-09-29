<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NetCode\Identity\Application\Ports\AuthenticatedUser;

final class MeResource extends JsonResource
{
    public function __construct(
        private readonly AuthenticatedUser $user,
    ) {
        parent::__construct($user);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'realm_id' => $this->user->realmId,
            'email_verified' => $this->user->emailVerified,
        ];
    }
}
