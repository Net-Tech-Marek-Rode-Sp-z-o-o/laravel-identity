<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TokenResource extends JsonResource
{
    public function __construct(
        private readonly string $token,
    ) {
        parent::__construct($token);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
