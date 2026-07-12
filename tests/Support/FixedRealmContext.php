<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\RealmContext;
use NetCode\Identity\Domain\ValueObjects\RealmId;

final class FixedRealmContext implements RealmContext
{
    public function __construct(
        private RealmId|null $realm = null,
    ) {}

    public function current(): RealmId|null
    {
        return $this->realm;
    }
}
