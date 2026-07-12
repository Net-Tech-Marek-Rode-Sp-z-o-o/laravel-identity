<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resources;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NetCode\Identity\Application\Commands\InviteUser\IssuedInvitation;

final class InvitationResource extends JsonResource
{
    public function __construct(
        private readonly IssuedInvitation $invitation,
    ) {
        parent::__construct($invitation);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->invitation->id,
            'email' => $this->invitation->email,
            'expires_at' => $this->invitation->expiresAt->format(DateTimeInterface::ATOM),
        ];
    }
}
