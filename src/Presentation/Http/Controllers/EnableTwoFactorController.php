<?php

declare(strict_types=1);

namespace NetCode\Identity\Presentation\Http\Controllers;

use NetCode\Bus\Command\CommandBus;
use NetCode\Identity\Application\Command\EnableTwoFactor\EnableTwoFactor;
use NetCode\Identity\Application\Port\CurrentUser;
use NetCode\Identity\Domain\ValueObjects\UserId;
use NetCode\Identity\Presentation\Http\Resource\TwoFactorEnrolmentResource;

final readonly class EnableTwoFactorController
{
    public function __construct(
        private CommandBus $bus,
        private CurrentUser $currentUser,
    ) {}

    public function __invoke(): TwoFactorEnrolmentResource
    {
        $enrolment = $this->bus->dispatch(new EnableTwoFactor(
            userId: UserId::fromString($this->currentUser->user()->id),
        ));

        return new TwoFactorEnrolmentResource($enrolment);
    }
}
