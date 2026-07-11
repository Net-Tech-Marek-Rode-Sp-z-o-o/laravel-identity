<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\Logout;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(LogoutHandler::class)]
final readonly class Logout implements Command {}
