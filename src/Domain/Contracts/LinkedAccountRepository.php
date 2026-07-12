<?php

declare(strict_types=1);

namespace NetCode\Identity\Domain\Contracts;

use NetCode\Identity\Domain\LinkedAccount;
use NetCode\Identity\Domain\SocialProvider;
use NetCode\Identity\Domain\ValueObjects\LinkedAccountId;

interface LinkedAccountRepository
{
    public function nextId(): LinkedAccountId;

    public function findByProvider(SocialProvider $provider, string $providerId): LinkedAccount|null;

    public function save(LinkedAccount $account): void;
}
