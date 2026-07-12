<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

final class AcceptInvitationData extends Data
{
    public function __construct(
        public string $token,
        #[Max(255)]
        public string $name,
        #[Min(8)]
        public string $password,
    ) {}
}
