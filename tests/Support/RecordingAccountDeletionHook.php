<?php

declare(strict_types=1);

namespace NetCode\Identity\Tests\Support;

use NetCode\Identity\Application\Ports\AccountDeletionHook;
use NetCode\Identity\Application\Ports\DeletedUser;

final class RecordingAccountDeletionHook implements AccountDeletionHook
{
    public DeletedUser|null $user = null;

    public function afterDeletion(DeletedUser $user): void
    {
        $this->user = $user;
    }
}
