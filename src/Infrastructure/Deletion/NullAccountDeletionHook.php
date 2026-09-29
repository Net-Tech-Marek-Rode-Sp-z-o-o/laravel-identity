<?php

declare(strict_types=1);

namespace NetCode\Identity\Infrastructure\Deletion;

use NetCode\Identity\Application\Ports\AccountDeletionHook;
use NetCode\Identity\Application\Ports\DeletedUser;

final class NullAccountDeletionHook implements AccountDeletionHook
{
    public function afterDeletion(DeletedUser $user): void {}
}
