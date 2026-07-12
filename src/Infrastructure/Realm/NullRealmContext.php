<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Realm;

use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Domain\ValueObjects\RealmId;

final class NullRealmContext implements RealmContext
{
    public function current(): RealmId|null
    {
        return null;
    }
}
