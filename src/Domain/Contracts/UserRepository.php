<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Contracts;

use NetCode\Identity\Domain\Exceptions\UserNotFoundException;
use NetCode\Identity\Domain\User;
use NetCode\Identity\Domain\ValueObjects\Email;
use NetCode\Identity\Domain\ValueObjects\RealmId;
use NetCode\Identity\Domain\ValueObjects\UserId;

interface UserRepository
{
    public function nextId(): UserId;

    public function findByEmail(RealmId|null $realm, Email $email): User|null;

    /** @throws UserNotFoundException */
    public function getById(UserId $id): User;

    public function save(User $user): void;
}
