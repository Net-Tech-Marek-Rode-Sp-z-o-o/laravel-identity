<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use NetCode\Identity\Application\Command\Login\LoginResult;

final class LoginResource extends JsonResource
{
    public function __construct(
        private readonly LoginResult $result,
    ) {
        parent::__construct($result);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if ($this->result->requiresTwoFactor()) {
            return [
                'two_factor' => true,
                'challenge_token' => $this->result->challengeToken,
            ];
        }

        return [
            'token' => $this->result->token,
        ];
    }
}
