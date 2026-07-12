<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Data;

use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;

final class RevokeInvitationData extends Data
{
    public function __construct(
        #[FromRouteParameter('invitationId')]
        public string $invitationId,
    ) {}
}
