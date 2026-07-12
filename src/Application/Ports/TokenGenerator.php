<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface TokenGenerator
{
    public function generate(): string;
}
