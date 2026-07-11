<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Data;

final class RequestPasswordResetData extends Data
{
    public function __construct(
        #[Email]
        public string $email,
    ) {}
}
