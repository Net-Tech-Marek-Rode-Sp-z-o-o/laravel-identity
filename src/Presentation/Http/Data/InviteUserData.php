<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Data;

final class InviteUserData extends Data
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        #[Email]
        public string $email,
        public array $metadata = [],
    ) {}
}
