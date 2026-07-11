<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

interface TokenGenerator
{
    public function generate(): string;
}
