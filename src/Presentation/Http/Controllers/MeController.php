<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use NetCode\Identity\Application\Ports\CurrentUser;
use NetCode\Identity\Presentation\Http\Resources\MeResource;

final readonly class MeController
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(): MeResource
    {
        return new MeResource($this->currentUser->user());
    }
}
