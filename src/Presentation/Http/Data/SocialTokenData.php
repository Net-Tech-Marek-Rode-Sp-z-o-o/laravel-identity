<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Data;

use Spatie\LaravelData\Data;

final class SocialTokenData extends Data
{
    public function __construct(
        public string $accessToken,
    ) {}
}
