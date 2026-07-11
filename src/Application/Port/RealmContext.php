<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Port;

use NetCode\Identity\Domain\ValueObjects\RealmId;

interface RealmContext
{
    public function current(): RealmId|null;
}
