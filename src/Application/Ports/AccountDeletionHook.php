<?php

declare(strict_types=1);

namespace NetCode\Identity\Application\Ports;

interface AccountDeletionHook
{
    /**
     * Runs inside the deletion transaction, after the user is soft-deleted and its tokens are revoked.
     */
    public function afterDeletion(DeletedUser $user): void;
}
