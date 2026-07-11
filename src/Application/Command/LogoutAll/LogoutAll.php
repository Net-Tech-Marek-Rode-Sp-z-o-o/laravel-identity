<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Command\LogoutAll;

use NetCode\Bus\Command\Command;
use NetCode\Bus\Command\HandledBy;

/** @implements Command<null> */
#[HandledBy(LogoutAllHandler::class)]
final readonly class LogoutAll implements Command {}
