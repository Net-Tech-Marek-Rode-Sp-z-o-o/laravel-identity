<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Data;

use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Data;

final class SocialAuthData extends Data
{
    public function __construct(
        #[FromRouteParameter('provider')]
        public string $provider,
        public string $accessToken,
    ) {}
}
