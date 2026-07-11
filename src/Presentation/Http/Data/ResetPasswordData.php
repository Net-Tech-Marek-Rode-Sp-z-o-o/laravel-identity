<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Data;

use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

final class ResetPasswordData extends Data
{
    public function __construct(
        public string $token,
        #[Min(8)]
        public string $password,
    ) {}
}
